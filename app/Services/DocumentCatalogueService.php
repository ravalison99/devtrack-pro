<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Document;
use App\Models\Project;
use App\Models\User;
use App\Support\LigneDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Catalogue des documents de l'application : documents de projet, cahiers des charges,
 * pièces jointes de tâches et anciens documents personnels.
 *
 * Règles d'accès :
 *  - administrateur et mentor : tous les documents ;
 *  - stagiaire (chef de groupe ou non) : uniquement les documents des projets qui lui sont attribués
 *    (et, pour les pièces jointes, des tâches qu'il a le droit de voir).
 */
class DocumentCatalogueService
{
    /**
     * @param  array{q?: ?string, origine?: ?string, project_id?: ?int, categorie?: ?string, type_rapport?: ?string, date_debut?: ?string, date_fin?: ?string}  $filtres
     */
    public function rechercher(User $user, array $filtres, int $parPage = 10): LengthAwarePaginator
    {
        $filtres = array_filter($filtres, fn ($valeur) => $valeur !== null && $valeur !== '');

        $lignes = $this->documents($user, $filtres)
            ->concat($this->cahiersDesCharges($user, $filtres))
            ->concat($this->piecesJointes($user, $filtres))
            ->sortByDesc(fn (LigneDocument $ligne) => $ligne->date?->getTimestamp() ?? 0)
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();

        return (new LengthAwarePaginator(
            $lignes->forPage($page, $parPage)->values(),
            $lignes->count(),
            $parPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        ))->withQueryString();
    }

    /** Projets proposés dans le filtre : tous pour mentor et administrateur, les siens pour un stagiaire. */
    public function projetsFiltrables(User $user): Collection
    {
        return Project::query()
            ->when(! $this->voitTout($user), fn (Builder $q) => $q->duStagiaire($user->id))
            ->orderBy('nom')
            ->get(['id', 'nom']);
    }

    protected function voitTout(User $user): bool
    {
        return $user->isAdmin() || $user->isMentor();
    }

    /** @return Collection<int, LigneDocument> */
    protected function documents(User $user, array $filtres): Collection
    {
        $origine = $filtres['origine'] ?? null;

        if ($origine !== null && ! in_array($origine, ['projet', 'personnel'], true)) {
            return collect();
        }

        $query = Document::query()->with([
            'project.groupe.chef',
            'utilisateur',
            'versions' => fn ($v) => $v->orderByDesc('numero_version'),
            'versions.utilisateur',
        ]);

        if (! $this->voitTout($user)) {
            $query->whereHas('project', fn (Builder $p) => $p->duStagiaire($user->id));
        }

        if ($origine === 'projet') {
            $query->whereNotNull('project_id');
        } elseif ($origine === 'personnel') {
            $query->whereNull('project_id');
        }

        if (isset($filtres['project_id'])) {
            $query->where('project_id', (int) $filtres['project_id']);
        }
        if (isset($filtres['categorie'])) {
            $query->where('categorie', $filtres['categorie']);
        }
        if (isset($filtres['type_rapport'])) {
            $query->where('type_rapport', $filtres['type_rapport']);
        }

        if (isset($filtres['q'])) {
            $like = $this->like($filtres['q']);
            $query->where(function (Builder $w) use ($like) {
                $w->where('titre', 'like', $like)
                    ->orWhereHas('utilisateur', fn (Builder $u) => $u->where('name', 'like', $like))
                    ->orWhereHas('versions', fn (Builder $v) => $v->where(function (Builder $x) use ($like) {
                        $x->where('nom_fichier', 'like', $like)
                            ->orWhereHas('utilisateur', fn (Builder $u) => $u->where('name', 'like', $like));
                    }))
                    ->orWhereHas('project', fn (Builder $p) => $p->where(function (Builder $x) use ($like) {
                        $x->where('nom', 'like', $like)
                            ->orWhereHas('groupe', fn (Builder $g) => $g->where('nom', 'like', $like));
                    }));
            });
        }

        // Un document est dans la période si au moins l'une de ses versions y a été déposée.
        if (isset($filtres['date_debut']) || isset($filtres['date_fin'])) {
            $query->whereHas('versions', fn (Builder $v) => $this->filtrerParDates($v, 'created_at', $filtres));
        }

        return $query->get()->map(function (Document $document) {
            $version = $document->versions->first();

            return new LigneDocument(
                origine: $document->project_id !== null ? 'projet' : 'personnel',
                titre: $document->titre,
                nomFichier: $version?->nom_fichier ?? ($version ? basename($version->fichier) : null),
                projet: $document->project,
                groupe: $document->project?->groupe,
                tache: null,
                deposant: $version?->utilisateur ?? $document->utilisateur,
                categorie: $document->categorie,
                typeRapport: $document->libelleTypeRapport(),
                versionActuelle: $version?->numero_version,
                nombreVersions: $document->versions->count(),
                date: $version?->created_at ?? $document->created_at,
                urlTelechargement: $version ? route('documents.download', [$document->id, $version->id]) : null,
                document: $document,
            );
        });
    }

    /** @return Collection<int, LigneDocument> */
    protected function cahiersDesCharges(User $user, array $filtres): Collection
    {
        $origine = $filtres['origine'] ?? null;

        // Catégorie et type de rapport n'existent que pour les documents déposés par les chefs de groupe.
        if (($origine !== null && $origine !== 'cahier') || isset($filtres['categorie']) || isset($filtres['type_rapport'])) {
            return collect();
        }

        $query = Project::query()
            ->with(['groupe.chef', 'mentor'])
            ->whereNotNull('cahier_charges_chemin');

        if (! $this->voitTout($user)) {
            $query->duStagiaire($user->id);
        }

        if (isset($filtres['project_id'])) {
            $query->whereKey((int) $filtres['project_id']);
        }

        if (isset($filtres['q'])) {
            $like = $this->like($filtres['q']);
            $query->where(function (Builder $w) use ($like) {
                $w->where('nom', 'like', $like)
                    ->orWhere('cahier_charges_nom', 'like', $like)
                    ->orWhereHas('mentor', fn (Builder $m) => $m->where('name', 'like', $like))
                    ->orWhereHas('groupe', fn (Builder $g) => $g->where('nom', 'like', $like));
            });
        }

        $this->filtrerParDates($query, 'created_at', $filtres);

        return $query->get()->map(fn (Project $projet) => new LigneDocument(
            origine: 'cahier',
            titre: 'Cahier des charges — '.$projet->nom,
            nomFichier: $projet->cahier_charges_nom,
            projet: $projet,
            groupe: $projet->groupe,
            tache: null,
            deposant: $projet->mentor,
            categorie: null,
            typeRapport: null,
            versionActuelle: null,
            nombreVersions: 1,
            date: $projet->created_at,
            urlTelechargement: route('projects.cahier.download', $projet->id),
        ));
    }

    /** @return Collection<int, LigneDocument> */
    protected function piecesJointes(User $user, array $filtres): Collection
    {
        $origine = $filtres['origine'] ?? null;

        if (($origine !== null && $origine !== 'tache') || isset($filtres['categorie']) || isset($filtres['type_rapport'])) {
            return collect();
        }

        $query = Attachment::query()->with(['utilisateur', 'task.project.groupe.chef']);

        if (! $this->voitTout($user)) {
            $query->whereHas('task', fn (Builder $t) => $t->visiblesPar($user->id));
        }

        if (isset($filtres['project_id'])) {
            $query->whereHas('task', fn (Builder $t) => $t->where('project_id', (int) $filtres['project_id']));
        }

        if (isset($filtres['q'])) {
            $like = $this->like($filtres['q']);
            $query->where(function (Builder $w) use ($like) {
                $w->where('nom_fichier', 'like', $like)
                    ->orWhereHas('utilisateur', fn (Builder $u) => $u->where('name', 'like', $like))
                    ->orWhereHas('task', fn (Builder $t) => $t->where(function (Builder $x) use ($like) {
                        $x->where('titre', 'like', $like)
                            ->orWhereHas('project', fn (Builder $p) => $p->where(function (Builder $y) use ($like) {
                                $y->where('nom', 'like', $like)
                                    ->orWhereHas('groupe', fn (Builder $g) => $g->where('nom', 'like', $like));
                            }));
                    }));
            });
        }

        $this->filtrerParDates($query, 'created_at', $filtres);

        return $query->get()
            ->filter(fn (Attachment $piece) => $piece->task !== null)
            ->map(fn (Attachment $piece) => new LigneDocument(
                origine: 'tache',
                titre: $piece->nom_fichier,
                nomFichier: $piece->nom_fichier,
                projet: $piece->task->project,
                groupe: $piece->task->project?->groupe,
                tache: $piece->task,
                deposant: $piece->utilisateur,
                categorie: null,
                typeRapport: null,
                versionActuelle: null,
                nombreVersions: 1,
                date: $piece->created_at,
                urlTelechargement: route('tasks.attachments.download', [$piece->task_id, $piece->id]),
            ));
    }

    protected function filtrerParDates(Builder $query, string $colonne, array $filtres): void
    {
        if (isset($filtres['date_debut'])) {
            $query->whereDate($colonne, '>=', $filtres['date_debut']);
        }
        if (isset($filtres['date_fin'])) {
            $query->whereDate($colonne, '<=', $filtres['date_fin']);
        }
    }

    protected function like(string $terme): string
    {
        return '%'.addcslashes(trim($terme), '%_\\').'%';
    }
}

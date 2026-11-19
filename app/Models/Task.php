<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Task extends Model
{
    protected $fillable = [
        'project_id',
        'assigne_id',
        'titre',
        'description',
        'priorite',
        'statut',
        'date_echeance',
    ];

    protected function casts(): array
    {
        return [
            'date_echeance' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** Stagiaire à qui la tâche a été confiée. */
    public function assigne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigne_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Un stagiaire voit une tâche si elle lui est assignée, s'il est chef du groupe du projet
     * (il répartit les tâches) ou, pour un projet historique, stagiaire du stage.
     */
    public function estVisiblePar(User $user): bool
    {
        $projet = $this->project;

        return $this->assigne_id === $user->id
            || $projet->estChef($user)
            || $projet->stage?->stagiaire_id === $user->id;
    }

    /**
     * Personnes concernées par la tâche : l'assigné, le chef de groupe qui répartit les tâches et le mentor.
     * Sans assigné (projet historique), tous les stagiaires du projet.
     *
     * @return Collection<int, User>
     */
    public function participants(): Collection
    {
        $projet = $this->project;

        return collect([$this->assigne, $projet->groupe?->chef, $projet->mentor])
            ->when($this->assigne === null, fn (Collection $c) => $c->merge($projet->stagiaires()))
            ->filter()
            ->unique('id')
            ->values();
    }

    /** La tâche est en retard : échéance dépassée et pas terminée. */
    public function estEnRetard(): bool
    {
        return $this->date_echeance !== null
            && $this->statut !== 'termine'
            && $this->date_echeance->lt(today());
    }

    /**
     * « Mes tâches » : celles qui me sont assignées (et, pour un projet historique sans assigné,
     * celles du stage dont je suis le stagiaire). Plus étroit que visiblesPar : le chef de groupe
     * voit toutes les tâches du projet, mais seules les siennes sont « ses » tâches.
     */
    public function scopeDe(Builder $query, int $stagiaireId): Builder
    {
        return $query->where(function (Builder $q) use ($stagiaireId) {
            $q->where('assigne_id', $stagiaireId)
                ->orWhere(function (Builder $w) use ($stagiaireId) {
                    $w->whereNull('assigne_id')
                        ->whereHas('project.stage', fn (Builder $s) => $s->where('stagiaire_id', $stagiaireId));
                });
        });
    }

    public function scopeVisiblesPar(Builder $query, int $stagiaireId): Builder
    {
        return $query->where(function (Builder $q) use ($stagiaireId) {
            $q->where('assigne_id', $stagiaireId)
                ->orWhereHas('project', function (Builder $p) use ($stagiaireId) {
                    $p->whereHas('groupe', fn (Builder $g) => $g->where('chef_id', $stagiaireId))
                        ->orWhereHas('stage', fn (Builder $s) => $s->where('stagiaire_id', $stagiaireId));
                });
        });
    }
}

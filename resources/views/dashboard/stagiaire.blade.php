@php
    $utilisateur = auth()->user();
    $heure = now()->hour;
    $salutation = $heure < 6 ? 'Bonne nuit' : ($heure < 18 ? 'Bonjour' : 'Bonsoir');
    $prenom = \Illuminate\Support\Str::of($utilisateur->name)->explode(' ')->first();

    $projet = $indicateurs['projet'];
    $groupe = $indicateurs['groupe'];
    $estChef = $indicateurs['est_chef'];
    $progression = $indicateurs['progression'];
    $equipe = $indicateurs['progression_equipe'];
    $journalRempli = $indicateurs['journal_rempli'];
@endphp

{{-- ===== Bandeau d'accueil ===== --}}
<section class="hero mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-4">
        <div class="flex-grow-1" style="min-width: 15rem">
            <div class="mb-2 d-flex flex-wrap gap-2">
                @if ($estChef)
                    <span class="pastille pastille-neutre" style="background:rgba(255,255,255,.18);color:#fff;border-color:transparent"><i class="bi bi-star-fill"></i>Chef de groupe</span>
                @elseif ($groupe)
                    <span class="pastille pastille-neutre" style="background:rgba(255,255,255,.18);color:#fff;border-color:transparent"><i class="bi bi-people-fill"></i>Membre du groupe</span>
                @endif
                @if ($projet)
                    <span class="pastille pastille-neutre" style="background:rgba(255,255,255,.18);color:#fff;border-color:transparent"><i class="bi bi-folder2-open"></i>{{ $projet->nom }}</span>
                @endif
            </div>

            <h1 class="h2 fw-bold mb-1">{{ $salutation }}, {{ $prenom }} 👋</h1>
            <p class="hero-sous-titre mb-3">
                @if ($progression['total'] === 0)
                    Aucune tâche ne vous est encore confiée. Profitez-en pour consulter votre projet.
                @elseif ($progression['en_retard'] > 0)
                    {{ $progression['en_retard'] }} tâche{{ $progression['en_retard'] > 1 ? 's sont' : ' est' }} en retard : c'est le moment de s'y remettre.
                @elseif ($progression['terminees'] === $progression['total'])
                    Bravo, toutes vos tâches sont terminées ! 🎉
                @else
                    Vous avez terminé {{ $progression['terminees'] }} tâche{{ $progression['terminees'] > 1 ? 's' : '' }} sur {{ $progression['total'] }}. Continuez comme ça !
                @endif
            </p>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('journal.index') }}" class="btn btn-hero">
                    <i class="bi bi-journal-text me-1"></i>{{ $journalRempli ? 'Mon journal' : 'Écrire mon journal du jour' }}
                </a>
                <a href="{{ route('tasks.index') }}" class="btn btn-hero-ghost">
                    <i class="bi bi-list-check me-1"></i>Mes tâches
                </a>
                @if ($estChef)
                    <a href="{{ route('tasks.create') }}" class="btn btn-hero-ghost">
                        <i class="bi bi-plus-lg me-1"></i>Créer une tâche
                    </a>
                @endif
            </div>
        </div>

        <div class="text-center">
            <x-anneau :pourcentage="$progression['pourcentage']" taille="8rem" libelle="Progression de mes tâches" class="mx-auto" />
            <div class="small mt-2 hero-sous-titre">Mes tâches terminées</div>
        </div>
    </div>
</section>

{{-- ===== Raccourcis par statut ===== --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><x-stat label="À faire" :valeur="$indicateurs['taches_a_faire']" icone="bi-circle" ton="#64748b" :lien="route('tasks.index', ['statut' => 'a_faire'])" /></div>
    <div class="col-6 col-lg-3"><x-stat label="En cours" :valeur="$indicateurs['taches_en_cours']" icone="bi-arrow-repeat" ton="#d97706" :lien="route('tasks.index', ['statut' => 'en_cours'])" /></div>
    <div class="col-6 col-lg-3"><x-stat label="En revue" :valeur="$indicateurs['taches_en_revue']" icone="bi-eye" ton="#2563eb" :lien="route('tasks.index', ['statut' => 'en_revue'])" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Terminées" :valeur="$indicateurs['taches_terminees']" icone="bi-check-circle" ton="#16a34a" :lien="route('tasks.index', ['statut' => 'termine'])" /></div>
</div>

<div class="row g-4">
    {{-- ===== À faire maintenant ===== --}}
    <div class="col-12 col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-lightning-charge-fill text-warning me-1"></i>À faire maintenant</span>
                <a href="{{ route('tasks.index') }}" class="small text-decoration-none">Tout voir <i class="bi bi-arrow-right"></i></a>
            </div>

            @forelse ($indicateurs['mes_taches'] as $tache)
                <a href="{{ route('tasks.show', $tache) }}" class="item-a-faire">
                    <span class="puce {{ $tache->priorite }}" title="Priorité {{ $tache->priorite }}"></span>
                    <span class="flex-grow-1" style="min-width:0">
                        <span class="d-block fw-semibold tronque">{{ $tache->titre }}</span>
                        <span class="d-block small text-muted tronque"><i class="bi bi-folder2-open me-1"></i>{{ $tache->project->nom }}</span>
                    </span>
                    <span class="text-end d-flex flex-column align-items-end gap-1">
                        <x-echeance :date="$tache->date_echeance" :statut="$tache->statut" />
                        <x-badge-statut :statut="$tache->statut" />
                    </span>
                </a>
            @empty
                <x-etat-vide icone="bi-emoji-smile" titre="Rien d'urgent pour le moment"
                    :texte="$progression['total'] > 0 ? 'Toutes vos tâches sont terminées.' : 'Vos tâches apparaîtront ici dès qu\'elles vous seront confiées.'" />
            @endforelse
        </div>
    </div>

    <div class="col-12 col-lg-5 d-flex flex-column gap-4">
        {{-- ===== Mon projet ===== --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-folder2-open me-1"></i>Mon projet</div>
            @if ($projet)
                <div class="card-body">
                    <a href="{{ route('projects.show', $projet->id) }}" class="h5 fw-bold text-decoration-none d-block mb-1">{{ $projet->nom }}</a>

                    @if ($groupe)
                        <div class="text-muted small mb-3">Groupe « {{ $groupe->nom }} »</div>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="avatar-pile">
                                @foreach ($groupe->membres as $membre)
                                    <x-avatar :user="$membre" />
                                @endforeach
                            </div>
                            <div class="small lh-sm">
                                <div class="text-muted">Chef de groupe</div>
                                <div class="fw-semibold">{{ $groupe->chef?->name ?? 'Compte supprimé' }}</div>
                            </div>
                        </div>
                    @endif

                    @if ($indicateurs['mentor'])
                        <div class="d-flex align-items-center gap-2 small mb-3">
                            <x-avatar :user="$indicateurs['mentor']" taille="sm" />
                            <span><span class="text-muted">Mentor :</span> <strong>{{ $indicateurs['mentor']->name }}</strong></span>
                        </div>
                    @endif

                    @if ($equipe && $equipe['total'] > 0)
                        <div class="small text-muted mb-1 d-flex justify-content-between">
                            <span>Avancement de l'équipe</span><strong>{{ $equipe['pourcentage'] }} %</strong>
                        </div>
                        <div class="barre-progression mb-3"><span style="width:{{ $equipe['pourcentage'] }}%" data-largeur="{{ $equipe['pourcentage'] }}"></span></div>
                    @endif

                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('projects.show', $projet->id) }}" class="btn btn-sm btn-primary"><i class="bi bi-eye me-1"></i>Ouvrir</a>
                        <a href="{{ route('kanban.show', $projet->id) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-kanban me-1"></i>Kanban</a>
                        @if ($projet->aUnCahierDesCharges())
                            <a href="{{ route('projects.cahier.download', $projet->id) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download me-1"></i>Cahier des charges</a>
                        @endif
                    </div>

                    @if ($indicateurs['stage'])
                        <div class="small text-muted mt-3 pt-3 border-top">
                            <i class="bi bi-calendar-range me-1"></i>Stage du {{ $indicateurs['stage']->date_debut->format('d/m/Y') }} au {{ $indicateurs['stage']->date_fin->format('d/m/Y') }}
                        </div>
                    @endif
                </div>
            @else
                <x-etat-vide icone="bi-hourglass-split" titre="Pas encore de projet"
                    texte="Votre Mentor vous ajoutera à un groupe de projet : vous serez prévenu(e) par notification." />
            @endif
        </div>

        {{-- ===== Activité récente ===== --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-activity me-1"></i>Activité récente</span>
                <a href="{{ route('notifications.index') }}" class="small text-decoration-none">Tout voir <i class="bi bi-arrow-right"></i></a>
            </div>
            @forelse ($indicateurs['activite'] as $notification)
                <a href="{{ route('notifications.open', $notification->id) }}" class="item-a-faire {{ $notification->read_at ? '' : 'notif-non-lue' }}">
                    <i class="bi {{ $notification->data['icone'] ?? 'bi-bell' }} fs-5 {{ $notification->read_at ? 'text-muted' : 'text-primary' }}"></i>
                    <span class="flex-grow-1 small" style="min-width:0">
                        <span class="d-block {{ $notification->read_at ? '' : 'fw-semibold' }}">{{ \Illuminate\Support\Str::limit($notification->data['message'] ?? $notification->data['titre'] ?? 'Notification', 95) }}</span>
                        <span class="text-muted">{{ $notification->created_at->diffForHumans() }}</span>
                    </span>
                </a>
            @empty
                <x-etat-vide icone="bi-bell-slash" titre="Rien de neuf" texte="Les commentaires, tâches et documents qui vous concernent apparaîtront ici." />
            @endforelse
        </div>
    </div>
</div>

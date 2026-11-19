@extends('layouts.app')

@section('title', 'Journal')

@section('content')
    @php
        $jours = $semaine['jours'];
        $serie = $semaine['serie'];
        $dateObj = \Carbon\Carbon::parse($dateChoisie);
        $estAujourdhui = $dateObj->isToday();
        $modification = $entreeChoisie !== null;
    @endphp

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h1 class="page-title h3 mb-1"><i class="bi bi-journal-text me-2"></i>Mon journal quotidien</h1>
            <p class="text-muted mb-0">Quelques lignes par jour pour garder une trace de ce que vous avez fait et appris.</p>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="text-end lh-sm">
                <div class="fw-bold fs-4"><i class="bi bi-fire text-warning me-1"></i><span data-compte="{{ $serie }}">{{ $serie }}</span></div>
                <div class="small text-muted">jour{{ $serie > 1 ? 's' : '' }} de suite</div>
            </div>
        </div>
    </div>

    {{-- ===== Semaine en cours ===== --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-semibold">Cette semaine</span>
                <span class="small text-muted">
                    @if ($semaine['rempli_aujourdhui'])
                        <i class="bi bi-check-circle-fill text-success me-1"></i>Journal du jour rempli
                    @else
                        <i class="bi bi-pencil me-1"></i>Pas encore d'entrée aujourd'hui
                    @endif
                </span>
            </div>

            <div class="semaine">
                @foreach ($jours as $jour)
                    @php
                        $classes = collect([
                            $jour['rempli'] ? 'rempli' : null,
                            $jour['manquant'] ? 'manquant' : null,
                            $jour['futur'] ? 'futur' : null,
                            $jour['aujourdhui'] ? 'aujourdhui' : null,
                            $jour['date'] === $dateChoisie ? 'selectionne' : null,
                        ])->filter()->implode(' ');
                    @endphp
                    <a href="{{ route('journal.index', ['date' => $jour['date']]) }}" class="jour {{ $classes }}"
                       title="{{ \Carbon\Carbon::parse($jour['date'])->format('d/m/Y') }}{{ $jour['rempli'] ? ' — rempli' : ($jour['manquant'] ? ' — manquant' : '') }}"
                       aria-label="{{ $jour['nom'] }} {{ $jour['numero'] }}{{ $jour['rempli'] ? ', rempli' : ($jour['manquant'] ? ', manquant' : '') }}">
                        <span class="jour-nom d-block">{{ $jour['nom'] }}</span>
                        <span class="jour-num d-block">{{ $jour['numero'] }}</span>
                        <span class="jour-etat d-block">
                            @if ($jour['rempli'])<i class="bi bi-check-circle-fill"></i>
                            @elseif ($jour['manquant'])<i class="bi bi-dash-circle"></i>
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- ===== Saisie ===== --}}
        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-pencil-square me-1"></i>
                    @if ($modification)
                        Modifier l'entrée du {{ $dateObj->format('d/m/Y') }}
                    @elseif ($estAujourdhui)
                        Entrée du jour
                    @else
                        Nouvelle entrée du {{ $dateObj->format('d/m/Y') }}
                    @endif
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('journal.store') }}">
                        @csrf

                        <x-champ label="Date" name="date" type="date" :value="old('date', $dateChoisie)" />
                        <x-champ label="Contenu" name="contenu" type="textarea" :value="old('contenu', $entreeChoisie?->contenu)"
                            placeholder="Qu'avez-vous fait aujourd'hui ? Qu'avez-vous appris ? Quelles difficultés ?" />

                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>{{ $modification ? 'Mettre à jour' : 'Enregistrer' }}
                            </button>
                            @if (! $estAujourdhui)
                                <a href="{{ route('journal.index') }}" class="btn btn-link btn-sm">Revenir à aujourd'hui</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ===== Historique ===== --}}
        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header"><i class="bi bi-clock-history me-1"></i>Historique</div>

                @if ($entries->isEmpty())
                    <x-etat-vide icone="bi-journal-plus" titre="Aucune entrée pour le moment"
                        texte="Votre première entrée apparaîtra ici sous forme de frise chronologique." />
                @else
                    <div class="frise">
                        @foreach ($entries as $entry)
                            <div class="frise-item">
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                    <strong>{{ \Illuminate\Support\Str::ucfirst($entry->date->copy()->locale('fr')->translatedFormat('l d F Y')) }}</strong>
                                    <a href="{{ route('journal.index', ['date' => $entry->date->toDateString()]) }}" class="small text-decoration-none"><i class="bi bi-pencil me-1"></i>Modifier</a>
                                </div>
                                <p class="mb-0 text-muted" style="white-space: pre-line">{{ $entry->contenu }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($entries->hasPages())
                    <div class="card-footer">
                        {{ $entries->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

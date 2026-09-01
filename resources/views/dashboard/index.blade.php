<h1>Tableau de bord</h1>

@if (isset($indicateurs['stages_actifs']))
    <div style="border:1px solid #ccc; padding:12px; margin-bottom:8px;">
        <strong>{{ $indicateurs['stages_actifs'] }}</strong> stage(s) actif(s)
    </div>
@endif

@if (isset($indicateurs['rapports_recus']))
    <div style="border:1px solid #ccc; padding:12px; margin-bottom:8px;">
        <strong>{{ $indicateurs['rapports_recus'] }}</strong> rapport(s) reçu(s) de vos stagiaires
    </div>
@endif

@if (isset($indicateurs['taches_a_faire']))
    <div style="display:flex; gap:12px;">
        <div style="border:1px solid #ccc; padding:12px;">À faire : <strong>{{ $indicateurs['taches_a_faire'] }}</strong></div>
        <div style="border:1px solid #ccc; padding:12px;">En cours : <strong>{{ $indicateurs['taches_en_cours'] }}</strong></div>
        <div style="border:1px solid #ccc; padding:12px;">En revue : <strong>{{ $indicateurs['taches_en_revue'] }}</strong></div>
        <div style="border:1px solid #ccc; padding:12px;">Terminées : <strong>{{ $indicateurs['taches_terminees'] }}</strong></div>
    </div>
@endif

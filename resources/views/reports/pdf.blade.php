<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; border-bottom: 2px solid #333; padding-bottom: 8px; }
        .meta { color: #666; margin-bottom: 20px; }
        .contenu { white-space: pre-line; line-height: 1.6; }
    </style>
</head>
<body>
    <h1>Rapport hebdomadaire — Semaine {{ $report->semaine }}</h1>
    <p class="meta">
        Stagiaire : {{ $report->stagiaire->name }}<br>
        Date de soumission : {{ $report->updated_at->format('d/m/Y') }}
    </p>
    <div class="contenu">
        {{ $report->contenu }}
    </div>
</body>
</html>

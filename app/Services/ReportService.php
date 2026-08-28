<?php

namespace App\Services;

use App\Events\WeeklyReportSubmitted;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Repositories\Contracts\WeeklyReportRepositoryInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ReportService
{
    public function __construct(protected WeeklyReportRepositoryInterface $reports) {}

    public function soumettre(User $stagiaire, int $semaine, string $contenu): WeeklyReport
    {
        $report = $this->reports->findByStagiaireAndSemaine($stagiaire->id, $semaine);

        if ($report !== null) {
            $report = $this->reports->update($report, [
                'contenu' => $contenu,
                'statut' => 'soumis',
            ]);
        } else {
            $report = $this->reports->create([
                'stagiaire_id' => $stagiaire->id,
                'semaine' => $semaine,
                'contenu' => $contenu,
                'statut' => 'soumis',
            ]);
        }

        $cheminPdf = $this->genererPdf($report);
        $report = $this->reports->update($report, ['fichier_pdf' => $cheminPdf]);

        WeeklyReportSubmitted::dispatch($report);

        return $report;
    }

    protected function genererPdf(WeeklyReport $report): string
    {
        $pdf = Pdf::loadView('reports.pdf', ['report' => $report]);

        $nomFichier = "rapport-{$report->stagiaire_id}-semaine{$report->semaine}.pdf";
        $chemin = "reports/{$nomFichier}";

        Storage::disk('local')->put($chemin, $pdf->output());

        return $chemin;
    }
}

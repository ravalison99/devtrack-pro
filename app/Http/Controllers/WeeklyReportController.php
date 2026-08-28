<?php

namespace App\Http\Controllers;

use App\Models\WeeklyReport;
use App\Repositories\Contracts\WeeklyReportRepositoryInterface;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WeeklyReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
        protected WeeklyReportRepositoryInterface $reports
    ) {}

    public function index()
    {
        $reports = $this->reports->findByStagiaire(auth()->id());
        return view('reports.index', compact('reports'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'semaine' => ['required', 'integer', 'min:1', 'max:12'],
            'contenu' => ['required', 'string', 'max:5000'],
        ]);

        $this->reportService->soumettre(auth()->user(), $data['semaine'], $data['contenu']);

        return redirect()
            ->route('reports.index')
            ->with('success', 'Rapport soumis avec succès.');
    }

    public function download(int $id)
    {
        $report = WeeklyReport::findOrFail($id);

        abort_unless(auth()->user()->can('view', $report), 403);

        return Storage::disk('local')->download($report->fichier_pdf);
    }
}

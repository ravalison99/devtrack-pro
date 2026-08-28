<?php

namespace App\Repositories\Eloquent;

use App\Models\WeeklyReport;
use App\Repositories\Contracts\WeeklyReportRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentWeeklyReportRepository implements WeeklyReportRepositoryInterface
{
    public function findByStagiaireAndSemaine(int $stagiaireId, int $semaine): ?WeeklyReport
    {
        return WeeklyReport::where('stagiaire_id', $stagiaireId)
            ->where('semaine', $semaine)
            ->first();
    }

    public function findByStagiaire(int $stagiaireId): Collection
    {
        return WeeklyReport::where('stagiaire_id', $stagiaireId)
            ->orderByDesc('semaine')
            ->get();
    }

    public function create(array $data): WeeklyReport
    {
        return WeeklyReport::create($data);
    }

    public function update(WeeklyReport $report, array $data): WeeklyReport
    {
        $report->update($data);
        return $report;
    }
}

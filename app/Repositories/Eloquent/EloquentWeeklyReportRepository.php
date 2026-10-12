<?php

namespace App\Repositories\Eloquent;

use App\Models\WeeklyReport;
use App\Repositories\Contracts\WeeklyReportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentWeeklyReportRepository implements WeeklyReportRepositoryInterface
{
    public function findByStagiaireAndSemaine(int $stagiaireId, int $semaine): ?WeeklyReport
    {
        return WeeklyReport::where('stagiaire_id', $stagiaireId)
            ->where('semaine', $semaine)
            ->first();
    }

    public function paginateByStagiaire(int $stagiaireId, int $parPage = 5): LengthAwarePaginator
    {
        return WeeklyReport::where('stagiaire_id', $stagiaireId)
            ->orderByDesc('semaine')
            ->paginate($parPage)
            ->withQueryString();
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

    public function countForMentor(int $mentorId): int
    {
        return WeeklyReport::whereHas('stagiaire.stagesEnTantQueStagiaire', function ($query) use ($mentorId) {
            $query->where('mentor_id', $mentorId);
        })->count();
    }
}

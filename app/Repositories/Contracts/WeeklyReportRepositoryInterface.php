<?php

namespace App\Repositories\Contracts;

use App\Models\WeeklyReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface WeeklyReportRepositoryInterface
{
    public function findByStagiaireAndSemaine(int $stagiaireId, int $semaine): ?WeeklyReport;
    public function paginateByStagiaire(int $stagiaireId, int $parPage = 5): LengthAwarePaginator;
    public function create(array $data): WeeklyReport;
    public function update(WeeklyReport $report, array $data): WeeklyReport;
    public function countForMentor(int $mentorId): int;
}

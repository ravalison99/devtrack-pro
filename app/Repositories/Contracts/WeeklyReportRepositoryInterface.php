<?php

namespace App\Repositories\Contracts;

use App\Models\WeeklyReport;
use Illuminate\Database\Eloquent\Collection;

interface WeeklyReportRepositoryInterface
{
    public function findByStagiaireAndSemaine(int $stagiaireId, int $semaine): ?WeeklyReport;
    public function findByStagiaire(int $stagiaireId): Collection;
    public function create(array $data): WeeklyReport;
    public function update(WeeklyReport $report, array $data): WeeklyReport;
    public function countForMentor(int $mentorId): int;
}

<?php

namespace App\Events;

use App\Models\WeeklyReport;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WeeklyReportSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(public WeeklyReport $report) {}
}

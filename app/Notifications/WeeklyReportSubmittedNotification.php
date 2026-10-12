<?php

namespace App\Notifications;

use App\Models\WeeklyReport;

class WeeklyReportSubmittedNotification extends BaseNotification
{
    public function __construct(protected WeeklyReport $report) {}

    protected function contenuMail(): array
    {
        return [
            'Nouveau rapport hebdomadaire soumis',
            "{$this->report->stagiaire->name} a soumis son rapport de la semaine {$this->report->semaine}.",
            'Voir le rapport',
            url("/reports/{$this->report->id}/download"),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'report_id' => $this->report->id,
            'stagiaire' => $this->report->stagiaire->name,
            'semaine' => $this->report->semaine,
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\WeeklyReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class WeeklyReportSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(protected WeeklyReport $report) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouveau rapport hebdomadaire soumis')
            ->line("{$this->report->stagiaire->name} a soumis son rapport de la semaine {$this->report->semaine}.")
            ->action('Voir le rapport', url("/reports/{$this->report->id}/download"));
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

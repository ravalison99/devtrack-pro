<?php

namespace App\Listeners;

use App\Events\TaskStatusChanged;
use Illuminate\Support\Facades\Log;

class LogTaskStatusChanged
{
    public function handle(TaskStatusChanged $event): void
    {
        Log::info("Tâche #{$event->task->id} : statut changé de '{$event->ancienStatut}' à '{$event->nouveauStatut}'.");
    }
}

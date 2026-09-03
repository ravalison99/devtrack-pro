<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Stage;

class StageObserver
{
    public function created(Stage $stage): void
    {
        AuditLog::create([
            'utilisateur_id' => auth()->id(),
            'action' => 'created',
            'modele' => Stage::class,
            'modele_id' => $stage->id,
        ]);
    }

    public function updated(Stage $stage): void
    {
        AuditLog::create([
            'utilisateur_id' => auth()->id(),
            'action' => 'updated',
            'modele' => Stage::class,
            'modele_id' => $stage->id,
        ]);
    }
}

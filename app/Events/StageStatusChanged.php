<?php

namespace App\Events;

use App\Models\Stage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StageStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Stage $stage,
        public string $ancienStatut,
        public string $nouveauStatut
    ) {}
}

<?php

namespace App\Events;

use App\Models\Stage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StageCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Stage $stage) {}
}

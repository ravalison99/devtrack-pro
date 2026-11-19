<?php

namespace App\Events;

use App\Models\Groupe;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupeCree
{
    use Dispatchable, SerializesModels;

    public function __construct(public Groupe $groupe) {}
}

<?php

namespace App\Providers;

use App\Models\Stage;
use App\Observers\StageObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Stage::observe(StageObserver::class);
    }
}

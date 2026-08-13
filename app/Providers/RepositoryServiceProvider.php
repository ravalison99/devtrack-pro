<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\Contracts\UserRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentUserRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\StageRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentStageRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\ProjectRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentProjectRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\TaskRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentTaskRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}

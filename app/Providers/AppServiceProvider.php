<?php

namespace App\Providers;

use GrahamCampbell\GitHub\GitHubServiceProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(GitHubServiceProvider::class);
    }
}

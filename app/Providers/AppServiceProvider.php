<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Global shares moved to HandleInertiaRequests middleware to align with Inertia conventions.
        
        // Registrar policies de moderação
        Gate::policy(\App\Models\Community::class, \App\Policies\CommunityPolicy::class);
        
        // Gate personalizado para ações de moderação
        Gate::define('performAction', function ($user, $community, $action) {
            return app(\App\Policies\ModerationPolicy::class)->performAction($user, $community, $action);
        });
    }
}

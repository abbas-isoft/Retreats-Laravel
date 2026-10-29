<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        // This API's authenticated User records represent dashboard users. Keep
        // the ability centralized so a role-aware policy can replace it later
        // without changing the protected ordering routes.
        Gate::define('manage-content-order', fn (User $user): bool => $user->exists);
        Gate::define('manage-content-seo', fn (User $user): bool => $user->exists);
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The default permission cache scope is the landlord context; tenant
        // switches re-scope it via ScopePermissionCacheTask.
        config(['permission.cache.key' => \Modules\Core\Tasks\ScopePermissionCacheTask::BASE_KEY.'.landlord']);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/landlord'));
    }
}

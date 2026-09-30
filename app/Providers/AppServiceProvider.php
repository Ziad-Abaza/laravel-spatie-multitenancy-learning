<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Tasks\ScopePermissionCacheTask;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The default permission cache scope is the landlord context; tenant
        // switches re-scope it via ScopePermissionCacheTask.
        config(['permission.cache.key' => ScopePermissionCacheTask::BASE_KEY.'.landlord']);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/landlord'));
    }
}

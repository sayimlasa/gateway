<?php

namespace App\Providers;

use routes;
use Illuminate\Support\Facades\Route;
//use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider; // ✅ correct


class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */

    public function boot(): void
    {
       $this->routes(function () {
            // Load default web routes
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // Load default API routes
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Load custom API route file (e.g., api_users.php)
            Route::middleware('api')
                ->prefix('api') // This makes routes like /api/api-users/register
                ->group(base_path('routes/api_users.php'));
        });
    }
}

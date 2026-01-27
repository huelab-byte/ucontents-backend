<?php

declare(strict_types=1);

namespace Modules\Authentication\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->routes(function () {
            Route::middleware('web')
                ->group(module_path('Authentication', '/routes/web.php'));
            
            Route::middleware('api')
                ->prefix('api')
                ->group(module_path('Authentication', '/routes/api.php'));
        });
    }
}

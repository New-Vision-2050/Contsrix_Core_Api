<?php

declare(strict_types=1);

namespace Modules\Attendance\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The module namespace to assume when generating URLs to actions.
     *
     * @var string
     */
    protected $moduleNamespace = 'Modules\Attendance\Controllers';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();
        $this->mapConstraintRoutes();
        $this->mapHierarchyRoutes();
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api/v1')
            ->middleware(['api', 'auth:api',\Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])
            ->namespace($this->moduleNamespace)
            ->group(module_path('Attendance', 'Resources/routes/api.php'));
    }

    /**
     * Define the "constraints" routes for the application.
     *
     * These routes are for attendance constraints functionality.
     *
     * @return void
     */
    protected function mapConstraintRoutes()
    {
        Route::prefix('api/v1')
            ->middleware(['api', 'auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])
            ->namespace($this->moduleNamespace)
            ->group(module_path('Attendance', 'Routes/attendance_constraints.php'));
    }
        protected function mapHierarchyRoutes()
    {
        Route::prefix('api/v1')
            ->middleware(['api', 'auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])
            ->namespace($this->moduleNamespace)
            ->group(module_path('Attendance', 'Routes/management_hierarchy.php'));
    }
}

<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class EmployeeTaskRouteServiceProvider extends ServiceProvider
{
    protected $moduleNamespace = 'Modules\EmployeeTask\Controllers';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapApiRoutes();
    }

    protected function mapApiRoutes(): void
    {
        Route::prefix('api/v1')
            ->middleware(['api', 'auth:api', \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class])
            ->namespace($this->moduleNamespace)
            ->group(module_path('EmployeeTask', 'Routes/employee_tasks.php'));
    }
}

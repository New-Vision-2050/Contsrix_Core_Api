<?php

declare(strict_types=1);

namespace Modules\SubEntity\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Modules\SubEntity\Models\SubEntity;
use Modules\SubEntity\Observers\SubEntityObserver;

class SubEntityServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'SubEntity';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerMigrations();
        $this->registerObservers();
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    protected function registerObservers(): void
    {
        SubEntity::observe(SubEntityObserver::class);
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/sub_entities')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Resources/config/config.php',
            'SubEntity::config'
        );
    }

    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

}

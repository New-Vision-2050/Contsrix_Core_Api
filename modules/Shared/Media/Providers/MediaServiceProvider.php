<?php

declare(strict_types=1);

namespace Modules\Shared\Media\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Modules\Shared\Media\Services\FileUploadService;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;
use Modules\Shared\Media\MediaLibrary\CustomPathGenerator;
use Modules\Shared\Media\Models\CustomMedia;
use Modules\Shared\Media\Observers\CustomMediaObserver;
class MediaServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'Media';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        //$this->registerConfig();
        $this->registerMigrations();
        $this->registerObservers();
        app()->bind(PathGenerator::class, \Modules\Shared\Media\MediaLibrary\CustomPathGenerator::class);
    }

    /**
     * Register model observers.
     *
     * @return void
     */
    protected function registerObservers(): void
    {
        CustomMedia::observe(CustomMediaObserver::class);
    }

    public function register(): void
    {
        $this->app->singleton(FileUploadService::class, function () {
            return new FileUploadService();
        });
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/media')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}

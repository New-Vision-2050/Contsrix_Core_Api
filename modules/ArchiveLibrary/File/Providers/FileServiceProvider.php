<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Modules\ArchiveLibrary\File\Models\File;
use Modules\ArchiveLibrary\File\Observers\FileObserver;

class FileServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'File';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        //$this->registerConfig();
        $this->registerMigrations();
        
        // Register File observer for automatic storage limit tracking
        File::observe(FileObserver::class);
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/files')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}

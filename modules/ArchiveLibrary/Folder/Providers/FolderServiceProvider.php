<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Modules\ArchiveLibrary\Folder\Observers\UserFolderObserver;
use Modules\User\Models\User;

class FolderServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'Folder';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerMigrations();
        
        User::observe(UserFolderObserver::class);
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/folders')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}

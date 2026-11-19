<?php

declare(strict_types=1);

namespace Modules\Forum\Providers;

use BasePackage\Shared\Module\ModuleServiceProvider;
use Illuminate\Support\Facades\Route;

class ForumServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'Forum';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerMigrations();
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/forum')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');
    }
}

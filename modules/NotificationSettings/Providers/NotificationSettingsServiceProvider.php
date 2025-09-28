<?php

declare(strict_types=1);

namespace Modules\NotificationSettings\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Modules\NotificationSettings\Commands\SendDocumentNotificationsCommand;

class NotificationSettingsServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'NotificationSettings';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        //$this->registerConfig();
        $this->registerMigrations();
        $this->registerCommands();
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    /**
     * Register the module commands
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SendDocumentNotificationsCommand::class,
            ]);
        }
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/notification_settings')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}

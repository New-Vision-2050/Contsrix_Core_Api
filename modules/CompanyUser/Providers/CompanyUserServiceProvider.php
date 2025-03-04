<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Modules\CompanyUser\Events\UserCreated;
use Modules\CompanyUser\Events\UserDeleted;
use Modules\CompanyUser\Events\UserUpdated;
use Modules\CompanyUser\Listeners\CreateUserInAuthListener;
use Modules\CompanyUser\Listeners\DeleteUserRoleInAuthListener;
use Modules\CompanyUser\Listeners\UpdateUserInAuthListener;

class CompanyUserServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'CompanyUser';
    }



    public function boot(): void
    {
        $this->registerTranslations();
        //$this->registerConfig();
        $this->registerMigrations();

        Event::listen(UserCreated::class,CreateUserInAuthListener::class );
        Event::listen(UserUpdated::class,UpdateUserInAuthListener::class );
        Event::listen(UserDeleted::class,DeleteUserRoleInAuthListener::class );

    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/company-users')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}

<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Modules\CompanyUser\Events\UserCreated;
use Modules\CompanyUser\Events\UserDeleted;
use Modules\CompanyUser\Events\UserUpdated;
use Modules\CompanyUser\Listeners\CreateUserInAuth;
use Modules\CompanyUser\Listeners\DeleteUserRoleInAuth;
use Modules\CompanyUser\Listeners\UpdateUserInAuth;

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

        Event::listen(UserCreated::class,CreateUserInAuth::class );
        Event::listen(UserUpdated::class,UpdateUserInAuth::class );
        Event::listen(UserDeleted::class,DeleteUserRoleInAuth::class );

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

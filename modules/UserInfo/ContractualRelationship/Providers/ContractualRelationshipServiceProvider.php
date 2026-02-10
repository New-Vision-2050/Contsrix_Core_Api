<?php

declare(strict_types=1);

namespace Modules\UserInfo\ContractualRelationship\Providers;

use BasePackage\Shared\Module\ModuleServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ContractualRelationshipServiceProvider extends ModuleServiceProvider
{
    protected string $moduleName = 'ContractualRelationship';

    protected string $moduleNameLower = 'contractual-relationship';

    public function boot(): void
    {
        $this->registerTranslations();
        //$this->registerConfig();
        $this->registerMigrations();
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/contractual-relationship')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }

    public static function getModuleName(): string
    {
        return "ContractualRelationship";
    }
}

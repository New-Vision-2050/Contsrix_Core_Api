<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Providers;

use Illuminate\Support\Facades\Route;
use BasePackage\Shared\Module\ModuleServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Company\CompanyCore\Models\Company;
use Modules\User\Models\User;

class ProjectManagementServiceProvider extends ModuleServiceProvider
{
    public static function getModuleName(): string
    {
        return 'ProjectManagement';
    }

    public function boot(): void
    {
        $this->registerTranslations();
        //$this->registerConfig();
        $this->registerMigrations();
        $this->registerMorphMap();
    }

    protected function registerMorphMap(): void
    {
        Relation::morphMap([
            'company' => Company::class,
            'individual' => User::class,
        ]);
    }

    public function register(): void
    {
        $this->registerRoutes();
    }

    public function mapRoutes(): void
    {
        Route::prefix('api/v1/projects')
            ->middleware('api')
            ->group($this->getModulePath() . '/Resources/routes/api.php');

    }
}

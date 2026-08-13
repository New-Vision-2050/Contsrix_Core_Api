<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Console;

use Illuminate\Console\Command;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectType\Services\ConstructionArchiveFolderService;

class SyncConstructionArchiveFoldersCommand extends Command
{
    protected $signature = 'archive:sync-construction-folders
                            {--company= : Only sync a specific company/tenant ID}
                            {--project= : Only sync a specific project ID}
                            {--dry-run : Preview without creating folders}';

    protected $description = 'Create empty Archive Library folders for Construction work orders (الانشاءات) under the same project root as Emergency';

    public function __construct(
        private readonly ConstructionArchiveFolderService $constructionArchiveFolders,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $companyId = $this->option('company');
        $projectId = $this->option('project');

        $projectsQuery = ProjectManagement::query()->withoutGlobalScopes();

        if ($projectId) {
            $projectsQuery->where('id', $projectId);
        }

        if ($companyId) {
            $projectsQuery->where('company_id', $companyId);
        }

        $projects = $projectsQuery->get(['id', 'name', 'company_id']);

        if ($projects->isEmpty()) {
            $this->warn('No matching projects found.');

            return self::SUCCESS;
        }

        $previousTenant = tenancy()->initialized ? tenant() : null;
        $totalWorkOrders = 0;
        $totalProjectsWithFolders = 0;

        foreach ($projects->groupBy('company_id') as $tenantCompanyId => $companyProjects) {
            $company = Company::withoutGlobalScopes()->find($tenantCompanyId);
            if (! $company) {
                continue;
            }

            tenancy()->end();
            tenancy()->initialize($company);

            foreach ($companyProjects as $project) {
                if ($dryRun) {
                    $count = $project->orderPermits()
                        ->whereNotNull('name')
                        ->where('name', '!=', '')
                        ->count();

                    if ($count > 0) {
                        $this->line("[DRY RUN] Project {$project->id}: would ensure الانشاءات + {$count} work-order folder(s)");
                        $totalWorkOrders += $count;
                        $totalProjectsWithFolders++;
                    }

                    continue;
                }

                $result = $this->constructionArchiveFolders->ensureProjectWorkOrderFolders(
                    (string) $project->id,
                    (string) $project->company_id,
                );

                if ($result['work_order_folders'] > 0) {
                    $this->line("Project {$project->id}: ensured الانشاءات + {$result['work_order_folders']} work-order folder(s)");
                    $totalWorkOrders += $result['work_order_folders'];
                    $totalProjectsWithFolders++;
                }
            }
        }

        tenancy()->end();
        if ($previousTenant) {
            tenancy()->initialize($previousTenant);
        }

        $this->info(
            ($dryRun ? '[DRY RUN] ' : '')
            ."Done. Projects touched: {$totalProjectsWithFolders}, work-order folders ensured: {$totalWorkOrders}."
        );

        return self::SUCCESS;
    }
}

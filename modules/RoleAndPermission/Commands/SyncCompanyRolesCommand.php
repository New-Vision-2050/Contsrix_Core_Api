<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\RoleAndPermission\Models\Role;
use Modules\RoleAndPermission\Repositories\RoleRepository;
use Modules\Subscription\Package\Repositories\PackageRepository;

class SyncCompanyRolesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:sync-companies
                            {--company-id= : Sync specific company ID only}
                            {--dry-run : Preview changes without applying them}
                            {--create-missing : Create missing super-admin/admin roles}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all companies\' roles: admin roles get all package permissions, all roles lose unauthorized permissions';

    private CompanyRepository $companyRepository;
    private RoleRepository $roleRepository;
    private PackageRepository $packageRepository;

    public function __construct(
        CompanyRepository $companyRepository,
        RoleRepository $roleRepository,
        PackageRepository $packageRepository
    ) {
        parent::__construct();
        $this->companyRepository = $companyRepository;
        $this->roleRepository = $roleRepository;
        $this->packageRepository = $packageRepository;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting company roles sync...');
        
        $companyId = $this->option('company-id');
        $dryRun = $this->option('dry-run');
        $createMissing = $this->option('create-missing');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be applied');
        }

        try {
            DB::transaction(function () use ($companyId, $dryRun, $createMissing) {
                $companies = $companyId 
                    ? collect([$this->companyRepository->findOneOrFail($companyId)])
                    : $this->getAllCompanies();

                $this->info("📊 Processing {$companies->count()} companies...");

                $stats = [
                    'companies_processed' => 0,
                    'roles_created' => 0,
                    'admin_roles_updated' => 0,
                    'other_roles_cleaned' => 0,
                    'permissions_added' => 0,
                    'permissions_removed' => 0,
                ];

                foreach ($companies as $company) {
                    $companyStats = $this->syncCompanyRoles($company, $dryRun, $createMissing);
                    $this->mergeStats($stats, $companyStats);
                }

                $this->displayStats($stats, $dryRun);

                if ($dryRun) {
                    // Rollback transaction in dry run mode
                    throw new \Exception('Dry run - rolling back changes');
                }
            });

            if (!$dryRun) {
                $this->info('✅ Company roles sync completed successfully!');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            if (!str_contains($e->getMessage(), 'Dry run')) {
                $this->error("❌ Error syncing company roles: " . $e->getMessage());
                return Command::FAILURE;
            }
            
            $this->info('✅ Dry run completed - no changes applied');
            return Command::SUCCESS;
        }
    }

    /**
     * Sync roles for a single company
     */
    private function syncCompanyRoles($company, bool $dryRun, bool $createMissing): array
    {
        $this->line("\n🏢 Processing Company: {$company->name} (ID: {$company->id})");

        $stats = [
            'companies_processed' => 1,
            'roles_created' => 0,
            'admin_roles_updated' => 0,
            'other_roles_cleaned' => 0,
            'permissions_added' => 0,
            'permissions_removed' => 0,
        ];

        // Get company's assigned packages and their permissions
        $companyWithPackages = $this->companyRepository->findWithPackages($company->id);
        $packageIds = $companyWithPackages->packages->pluck('id')->toArray();

        if (empty($packageIds)) {
            $this->warn("  ⚠️  No packages assigned to company {$company->name}");
            return $stats;
        }

        // Get all permissions from assigned packages
        $allowedPermissionIds = $this->getPackagePermissions($packageIds);
        $this->info("  📦 Packages: " . count($packageIds) . " | Permissions: " . count($allowedPermissionIds));

        // Get or create super-admin and admin roles
        $adminRolesResult = $this->getOrCreateAdminRoles($company->id, $createMissing, $dryRun);
        $stats['roles_created'] += $adminRolesResult['created'];

        // Sync permissions for admin roles (give them ALL package permissions)
        foreach ($adminRolesResult['roles'] as $role) {
            $roleStats = $this->syncAdminRolePermissions($role, $allowedPermissionIds, $dryRun);
            if ($roleStats['updated']) {
                $stats['admin_roles_updated']++;
            }
            $stats['permissions_added'] += $roleStats['added'];
            $stats['permissions_removed'] += $roleStats['removed'];
        }

        // Clean up ALL company roles (remove permissions not in packages)
        $cleanupStats = $this->cleanupAllCompanyRoles($company->id, $allowedPermissionIds, $dryRun);
        $stats['other_roles_cleaned'] += $cleanupStats['roles_cleaned'];
        $stats['permissions_removed'] += $cleanupStats['permissions_removed'];

        return $stats;
    }

    /**
     * Get all permissions from assigned packages
     */
    private function getPackagePermissions(array $packageIds): array
    {
        $packages = $this->packageRepository->findByIdsWithPermissions($packageIds);
        $allowedPermissionIds = [];

        foreach ($packages as $package) {
            foreach ($package->permissions as $permission) {
                $allowedPermissionIds[] = $permission->id;
            }
        }

        return array_unique($allowedPermissionIds);
    }

    /**
     * Get or create super-admin and admin roles for a company
     */
    private function getOrCreateAdminRoles(string $companyId, bool $createMissing, bool $dryRun): array
    {
        $adminRoleNames = ['super admin', 'admin'];
        $roles = [];
        $created = 0;

        foreach ($adminRoleNames as $roleName) {
            $role = Role::where('company_id', $companyId)
                ->where('name', $roleName)
                ->first();

            if (!$role && $createMissing) {
                if (!$dryRun) {
                    $role = Role::create([
                        'name' => $roleName,
                        'company_id' => $companyId,
                        'guard_name' => 'web',
                        'status' => 1,
                    ]);
                }
                $this->info("  ✨ Created role: {$roleName}");
                $created++;
            }

            if ($role) {
                $roles[] = $role;
            } else if (!$createMissing) {
                $this->warn("  ⚠️  Role '{$roleName}' not found (use --create-missing to create)");
            }
        }

        return [
            'roles' => $roles,
            'created' => $created
        ];
    }

    /**
     * Sync permissions for an admin role (give ALL package permissions)
     */
    private function syncAdminRolePermissions($role, array $allowedPermissionIds, bool $dryRun): array
    {
        $currentPermissionIds = $role->permissions()->pluck('id')->toArray();
        
        $permissionsToAdd = array_diff($allowedPermissionIds, $currentPermissionIds);
        $permissionsToRemove = array_diff($currentPermissionIds, $allowedPermissionIds);

        $updated = false;

        if (!empty($permissionsToAdd) && !$dryRun) {
            $role->permissions()->attach($permissionsToAdd);
            $updated = true;
        }

        if (!empty($permissionsToRemove) && !$dryRun) {
            $role->permissions()->detach($permissionsToRemove);
            $updated = true;
        }

        if (!empty($permissionsToAdd) || !empty($permissionsToRemove)) {
            $this->info("  🔧 Admin role '{$role->name}': +" . count($permissionsToAdd) . " -" . count($permissionsToRemove) . " permissions");
        }

        return [
            'updated' => $updated,
            'added' => count($permissionsToAdd),
            'removed' => count($permissionsToRemove)
        ];
    }

    /**
     * Clean up ALL company roles (remove permissions not in packages)
     */
    private function cleanupAllCompanyRoles(string $companyId, array $allowedPermissionIds, bool $dryRun): array
    {
        $allRoles = $this->roleRepository->findByCompanyId($companyId);
        $rolesCleanedCount = 0;
        $totalPermissionsRemoved = 0;

        foreach ($allRoles as $role) {
            $currentPermissionIds = $role->permissions()->pluck('id')->toArray();
            $permissionsToRemove = array_diff($currentPermissionIds, $allowedPermissionIds);

            if (!empty($permissionsToRemove)) {
                if (!$dryRun) {
                    $role->permissions()->detach($permissionsToRemove);
                }
                
                $rolesCleanedCount++;
                $totalPermissionsRemoved += count($permissionsToRemove);
                
                $this->info("  🧹 Cleaned role '{$role->name}': -" . count($permissionsToRemove) . " unauthorized permissions");
            }
        }

        return [
            'roles_cleaned' => $rolesCleanedCount,
            'permissions_removed' => $totalPermissionsRemoved
        ];
    }

    /**
     * Get all companies
     */
    private function getAllCompanies()
    {
        return $this->companyRepository->all();
    }

    /**
     * Merge statistics arrays
     */
    private function mergeStats(array &$stats, array $newStats): void
    {
        foreach ($newStats as $key => $value) {
            $stats[$key] += $value;
        }
    }

    /**
     * Display final statistics
     */
    private function displayStats(array $stats, bool $dryRun): void
    {
        $this->line("\n📈 SYNC SUMMARY:");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("Companies Processed: {$stats['companies_processed']}");
        $this->info("Admin Roles Created: {$stats['roles_created']}");
        $this->info("Admin Roles Updated: {$stats['admin_roles_updated']}");
        $this->info("Other Roles Cleaned: {$stats['other_roles_cleaned']}");
        $this->info("Permissions Added: {$stats['permissions_added']}");
        $this->info("Permissions Removed: {$stats['permissions_removed']}");
        
        if ($dryRun) {
            $this->warn("\n⚠️  This was a DRY RUN - no actual changes were made");
        }
    }
}

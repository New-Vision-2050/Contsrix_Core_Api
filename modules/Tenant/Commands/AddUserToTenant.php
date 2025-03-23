<?php

declare(strict_types=1);

namespace Modules\Tenant\Commands;

use Illuminate\Console\Command;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\Tenant\Models\Tenant;
use Stancl\Tenancy\Facades\Tenancy;

class AddUserToTenant extends Command
{
    protected $signature = 'tenant:add-user {tenant_id} {user_id} {--role=1} {--status=active}';
    protected $description = 'Add a user to a tenant database';

    public function handle()
    {
        $tenantId = $this->argument('tenant_id');
        $userId = $this->argument('user_id');
        $role = $this->option('role');
        $status = $this->option('status');
        
        // Find the tenant
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $this->error("Tenant not found: {$tenantId}");
            return 1;
        }
        
        // Find the user in the central database
        $user = CompanyUser::find($userId);
        if (!$user) {
            $this->error("User not found in central database: {$userId}");
            return 1;
        }
        
        // Initialize the tenant context
        Tenancy::initialize($tenant);
        
        // Check if the user already exists in the tenant database
        $tenantUser = \Modules\Tenant\Models\TenantUser::find($userId);
        if ($tenantUser) {
            $this->info("User already exists in tenant database");
            
            // Check if the user is associated with the tenant's company
            $companyIds = $tenantUser->companies->pluck('id')->toArray();
            if (!in_array($tenant->company_id, $companyIds)) {
                // Associate the user with the tenant's company
                $tenantUser->companies()->attach($tenant->company_id, [
                    'role' => $role,
                    'status' => $status
                ]);
                $this->info("User associated with tenant's company");
            } else {
                $this->info("User already associated with tenant's company");
            }
            
            return 0;
        }
        
        // Create the user in the tenant database
        $tenantUser = new \Modules\Tenant\Models\TenantUser();
        $tenantUser->id = $user->id;
        $tenantUser->name = $user->name;
        $tenantUser->email = $user->email;
        $tenantUser->password = $user->password;
        $tenantUser->save();
        
        // Associate the user with the tenant's company
        $tenantUser->companies()->attach($tenant->company_id, [
            'role' => $role,
            'status' => $status
        ]);
        
        $this->info("User added to tenant database successfully");
        return 0;
    }
}
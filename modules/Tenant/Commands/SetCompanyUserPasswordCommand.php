<?php

declare(strict_types=1);

namespace Modules\Tenant\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\Tenant\Models\Tenant;
use Stancl\Tenancy\Concerns\HasATenantArgument;

class SetCompanyUserPasswordCommand extends Command
{
    use HasATenantArgument;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:set-user-password
                            {email : The email of the company user}
                            {password : The password to set}
                            {--role= : The role to assign to the user for this company}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set a password for a company user within a tenant';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tenant = Tenant::find($this->argument('tenant'));
        
        if (!$tenant) {
            $this->error("Tenant not found: {$this->argument('tenant')}");
            return 1;
        }

        $email = $this->argument('email');
        $password = $this->argument('password');
        $role = $this->option('role');

        $result = $tenant->run(function () use ($email, $password, $role, $tenant) {
            $user = CompanyUser::where('email', $email)->first();
            
            if (!$user) {
                return false;
            }
            
            // Set the password
            $user->password = Hash::make($password);
            $user->save();
            
            // Update the role if provided
            if ($role) {
                $companyRelation = $user->companies()->where('company_id', $tenant->company_id)->first();
                if ($companyRelation) {
                    $user->companies()->updateExistingPivot($tenant->company_id, [
                        'role' => $role
                    ]);
                    return ['updated_role' => true];
                }
            }
            
            return true;
        });

        if (!$result) {
            $this->error("User with email {$email} not found in tenant {$tenant->id}");
            return 1;
        }

        $this->info("Password set successfully for user {$email} in tenant {$tenant->id}");
        
        if (is_array($result) && isset($result['updated_role']) && $result['updated_role']) {
            $this->info("Role updated to '{$role}' for user {$email} in tenant {$tenant->id}");
        }
        
        return 0;
    }
}
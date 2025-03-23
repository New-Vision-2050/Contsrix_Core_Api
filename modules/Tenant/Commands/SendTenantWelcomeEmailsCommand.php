<?php

declare(strict_types=1);

namespace Modules\Tenant\Commands;

use Illuminate\Console\Command;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\TenantWelcomeService;
use Stancl\Tenancy\Concerns\HasATenantArgument;

class SendTenantWelcomeEmailsCommand extends Command
{
    use HasATenantArgument;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:send-welcome-emails
                            {--all : Send to all users of the tenant}
                            {--email= : Send to a specific user email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send welcome emails to tenant users';

    /**
     * Execute the console command.
     *
     * @param TenantWelcomeService $welcomeService
     * @return int
     */
    public function handle(TenantWelcomeService $welcomeService)
    {
        $tenant = Tenant::find($this->argument('tenant'));
        
        if (!$tenant) {
            $this->error("Tenant not found: {$this->argument('tenant')}");
            return 1;
        }

        if ($this->option('all')) {
            $count = $welcomeService->sendWelcomeEmailsToAllCompanyUsers($tenant);
            $this->info("Sent {$count} welcome emails to users of tenant {$tenant->id}");
            return 0;
        }

        $email = $this->option('email');
        if (!$email) {
            $this->error('Please specify either --all or --email option');
            return 1;
        }

        $companyId = $tenant->company_id;
        if (!$companyId) {
            $this->error("Tenant {$tenant->id} has no associated company");
            return 1;
        }

        // Find the user by email in the company
        $user = null;
        $tenant->run(function () use (&$user, $email) {
            $user = \Modules\CompanyUser\Models\CompanyUser::where('email', $email)->first();
        });

        if (!$user) {
            $this->error("User with email {$email} not found in tenant {$tenant->id}");
            return 1;
        }

        $success = $welcomeService->sendWelcomeEmail($user, $tenant);
        
        if ($success) {
            $this->info("Welcome email sent to {$email} for tenant {$tenant->id}");
            return 0;
        } else {
            $this->error("Failed to send welcome email to {$email} for tenant {$tenant->id}");
            return 1;
        }
    }
}
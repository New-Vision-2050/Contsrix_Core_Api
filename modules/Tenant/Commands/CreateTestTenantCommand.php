<?php

declare(strict_types=1);

namespace Modules\Tenant\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyField\Models\CompanyField;
use Modules\Company\CompanyType\Models\CompanyType;
use Modules\CompanyUser\DTO\CreateCompanyUserCompanyRoleDTO;
use Modules\CompanyUser\DTO\CreateCompanyUserDTO;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Services\CompanyUserCRUDService;
use Modules\Country\Models\Country;
use Modules\JobTitle\Models\JobTitle;
use Modules\Shared\Currency\Models\Currency;
use Modules\Shared\Language\Models\Language;
use Modules\Shared\TimeZone\Models\TimeZone;
use Modules\Tenant\Services\TenantService;
use Modules\Tenant\Services\TenantWelcomeService;
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;

class CreateTestTenantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create-test
                            {--name=Test Company : The name of the company}
                            {--username=talent56: The username of the company (used for subdomain)}
                            {--email=company@example.com : The email of the company}
                            {--user-name=Test User : The name of the company user}
                            {--user-email=user4@example.com : The email of the company user}
                            {--user-password=password123 : The password of the company user}
                            {--user-role=1 : The role of the company user}
                            {--send-welcome-email : Whether to send a welcome email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test tenant with a company and user for testing';

    /**
     * Execute the console command.
     *
     * @param TenantService          $tenantService
     * @param CompanyUserCRUDService $companyUserService
     * @param TenantWelcomeService   $welcomeService
     *
     * @return int
     */
    public function handle(
        TenantService $tenantService,
        CompanyUserCRUDService $companyUserService,
        TenantWelcomeService $welcomeService
    ) {
        $this->info('Creating a test tenant...');
        $timezone = TimeZone::first();
        $country = Country::first();
        $currency = Currency::first();
        $language = Language::first();
        $companyField = CompanyField::first();
        $companyType = CompanyType::first();
        $user = User::first();
        $jobTitle = JobTitle::first();


        // Create a company
        $company = new Company();

        $company->id = Uuid::uuid4();
        $company->name = $this->option('name');
        $company->user_name = $this->option('username');
        $company->email = $this->option('email');
        $company->is_active = true;
        $company->country_id = $country->id;
        $company->company_type_id = $companyType->id;
        $company->company_field_id = $companyField->id;
        $company->general_manager_id = $user->id;
        $company->save();

        $this->info("Company created with ID: {$company->id}");

        // Create a tenant
        /** @var TenantService $tenantService */
        $tenantService = app()->make(TenantService::class);
        $tenant =  $tenantService->getTenantByCompanyId($company->id);
        $this->info("Tenant created with ID: {$tenant->id}");

        // Get the domain
        $domain = '';
        foreach ($tenant->domains as $tenantDomain) {
            $domain = $tenantDomain->domain;
            break;
        }
        $this->info("Tenant domain: {$domain}");


        // Create a company user
        $createDTO = new CreateCompanyUserDTO(
            firstName:     $this->option('user-name'),
            lastName:      $this->option('user-name'),
            email:         $this->option('user-email'),
            country_id:    $country->id,
            phone:         '1234567890',
            job_title_id:  $jobTitle->id,
            border_number: null,
            residence:     null,
            identity:      null,
            passport:      null,
            time_zone_id:  $timezone->id,
            language_id:   $language->id, currency_id: $currency->id
        );

        $companyRoleDTO = new CreateCompanyUserCompanyRoleDTO(
            company_id: Uuid::fromString($company->id),
            role:       "1"
        );

        $companyUser = $companyUserService->create($createDTO, $companyRoleDTO);
        $this->info("Company User created with ID: {$companyUser->id}");

        // Set password for the company user
        $tenant->run(function () use ($companyUser, $tenant) {
            $user = \Modules\CompanyUser\Models\CompanyUser::find($companyUser->id);
            if ($user) {
                $user->password = Hash::make($this->option('user-password'));
                $user->save();
                $this->info("Password set for user: {$user->email}");
            }
        });

        // Send welcome email if requested
        if ($this->option('send-welcome-email')) {
            $role = $this->option('user-role');
            $welcomeService->sendWelcomeEmail($companyUser, $tenant);
            $this->info("Welcome email sent to: {$companyUser->email} with role: {$role}");
        }

        $this->info("\nTest tenant setup complete!");
        $this->info("\nAccess your tenant at: http://{$domain}");
        $this->info("Login with:");
        $this->info("Email: {$this->option('user-email')}");
        $this->info("Password: {$this->option('user-password')}");

        return 0;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Notifications\TenantWelcomeNotification;

class TenantWelcomeService
{
    /**
     * @var TenantService
     */
    protected $tenantService;

    /**
     * TenantWelcomeService constructor.
     *
     * @param TenantService $tenantService
     */
    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    /**
     * Send a welcome email to a company user.
     *
     * @param CompanyUser $companyUser
     * @param Tenant|null $tenant
     * @return bool
     */
    public function sendWelcomeEmail(CompanyUser $companyUser, ?Tenant $tenant = null): bool
    {
        // If tenant is not provided, try to find it based on the company
        if (!$tenant) {
            $companyId = $companyUser->companies->first()->id ?? null;
            if (!$companyId) {
                return false;
            }
            
            $tenant = $this->tenantService->getTenantByCompanyId($companyId);
            if (!$tenant) {
                return false;
            }
        }

        // Check if the user belongs to the tenant's company
        $companyRelation = $companyUser->companies()->where('company_id', $tenant->company_id)->first();
        if (!$companyRelation) {
            return false;
        }
        
        // Get the user's role for this company
        $role = $companyRelation->pivot->role ?? 'user';

        // Generate a temporary password
        $temporaryPassword = $this->generateTemporaryPassword();
        
        // Update the user's password in the tenant database
        $success = $tenant->run(function () use ($companyUser, $temporaryPassword) {
            $user = CompanyUser::find($companyUser->id);
            if (!$user) {
                return false;
            }
            
            $user->password = Hash::make($temporaryPassword);
            $user->save();
            
            return true;
        });
        
        if (!$success) {
            return false;
        }
        
        // Send the welcome notification with company role information
        $companyUser->notify(new TenantWelcomeNotification($tenant, $temporaryPassword, $role));
        
        return true;
    }

    /**
     * Generate a temporary password.
     *
     * @return string
     */
    protected function generateTemporaryPassword(): string
    {
        return Str::random(12);
    }

    /**
     * Send welcome emails to all users of a company.
     *
     * @param Tenant $tenant
     * @return int Number of emails sent
     */
    public function sendWelcomeEmailsToAllCompanyUsers(Tenant $tenant): int
    {
        $companyId = $tenant->company_id;
        if (!$companyId) {
            return 0;
        }
        
        $companyUsers = CompanyUser::whereHas('companies', function ($query) use ($companyId) {
            $query->where('id', $companyId);
        })->get();
        
        $emailsSent = 0;
        
        foreach ($companyUsers as $companyUser) {
            if ($this->sendWelcomeEmail($companyUser, $tenant)) {
                $emailsSent++;
            }
        }
        
        return $emailsSent;
    }
}
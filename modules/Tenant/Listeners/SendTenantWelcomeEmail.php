<?php

declare(strict_types=1);

namespace Modules\Tenant\Listeners;

use Modules\CompanyUser\Events\UserCreated;
use Modules\Tenant\Services\TenantService;
use Modules\Tenant\Services\TenantWelcomeService;

class SendTenantWelcomeEmail
{
    /**
     * @var TenantService
     */
    protected $tenantService;

    /**
     * @var TenantWelcomeService
     */
    protected $welcomeService;

    /**
     * Create the event listener.
     *
     * @param TenantService $tenantService
     * @param TenantWelcomeService $welcomeService
     */
    public function __construct(
        TenantService $tenantService,
        TenantWelcomeService $welcomeService
    ) {
        $this->tenantService = $tenantService;
        $this->welcomeService = $welcomeService;
    }

    /**
     * Handle the event.
     *
     * @param UserCreated $event
     * @return void
     */
    public function handle(UserCreated $event): void
    {
        $companyUser = $event->user;
        
        // Get the company ID from the event or from the user's companies
        $companyId = $event->companyId ?? $companyUser->companies->first()->id ?? null;
        
        if (!$companyId) {
            return;
        }
        
        // Find the tenant for this company
        $tenant = $this->tenantService->getTenantByCompanyId($companyId);
        
        if (!$tenant) {
            return;
        }
        
        // Send the welcome email
        $this->welcomeService->sendWelcomeEmail($companyUser, $tenant);
    }
}
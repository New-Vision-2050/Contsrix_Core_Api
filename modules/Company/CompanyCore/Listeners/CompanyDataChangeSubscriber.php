<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Listeners;

use App\Exceptions\CustomException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;
use Modules\Company\CompanyCore\Events\CompanyAddressUpdated;
use Modules\Company\CompanyCore\Events\CompanyLegalDataUpdated;
use Modules\Company\CompanyCore\Services\CompanyCacheService;

/**
 * Listener for company data change events to clear cache
 */
class CompanyDataChangeSubscriber
{

    private CompanyCacheService $cacheService;


    public function __construct(CompanyCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }


    public function handleCompanyLegalDataUpdated(CompanyLegalDataUpdated $event)
    {
        $legalData = $event->legalData;
        $companyId = $legalData->company_id;
        $branchId = $legalData->management_hierarchy_id;


        // Clear cache for this specific data type
        $this->cacheService->clearCompanyCache($companyId, $branchId);

    }


    public function handleCompanyAddressUpdated(CompanyAddressUpdated $event)
    {

        $address = $event->companyAddress;
        $companyId = $address->company_id;
        $branchId = $address->management_hierarchy_id;

        // Clear cache for this specific data type
        $this->cacheService->clearCompanyCache($companyId, $branchId);

    }


    public function handleCompanyDocumentsUpdated($event)
    {
        $document = $event->document;
        $companyId = $document->company_id;
        $branchId = $document->management_hierarchy_id;

        // Clear cache for this specific data type
        $this->cacheService->clearCompanyCache($companyId, $branchId);

    }


    public function handleBranchUpdated($event)
    {
        $branch = $event->branch;
        $companyId = $branch->company_id;
        $branchId = $branch->id;

        // Clear cache for this specific data type
        $this->cacheService->clearCompanyCache($companyId, $branchId);


    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param Dispatcher $events
     * @return void
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(
            'Modules\Company\CompanyCore\Events\CompanyLegalDataUpdated',
            [CompanyDataChangeSubscriber::class, 'handleCompanyLegalDataUpdated']
        );

        $events->listen(
            'Modules\Company\CompanyCore\Events\CompanyLegalDataCreated',
            [CompanyDataChangeSubscriber::class, 'handleCompanyLegalDataUpdated']
        );

        $events->listen(
            'Modules\Company\CompanyCore\Events\CompanyLegalDataDeleted',
            [CompanyDataChangeSubscriber::class, 'handleCompanyLegalDataUpdated']
        );

        $events->listen(
            'Modules\Company\CompanyCore\Events\CompanyAddressUpdated',
            [CompanyDataChangeSubscriber::class, 'handleCompanyAddressUpdated']
        );

        $events->listen(
            'Modules\Company\CompanyCore\Events\CompanyOfficialDocumentUpdated',
            [CompanyDataChangeSubscriber::class, 'handleCompanyDocumentsUpdated']
        );

        $events->listen(
            'Modules\Company\ManagementHierarchy\Events\BranchUpdatedEvent',
            [CompanyDataChangeSubscriber::class, 'handleBranchUpdated']
        );
    }
}

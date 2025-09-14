<?php

namespace Modules\Company\ManagementHierarchy\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Services\DefaultConstraintService;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\Company\ManagementHierarchy\DTO\AssignUsersToManagementHierarchyDTO;
use Modules\Company\ManagementHierarchy\Events\CompanyCreatedEvent;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\ManagementHierarchy\Models\UserCanAccessManagementHierarchy;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;
use Modules\Company\ManagementHierarchy\Repositories\UserCanAccessManagementHierarchyRepository;
use Ramsey\Uuid\Uuid;

class CreateHierarchyListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        private ManagementHierarchyRepository $managementHierarchyRepository,
        private DefaultConstraintService $defaultConstraintService,
    )
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param object $event
     * @return void
     */
    public function handle(CompanyCreatedEvent $event)
    {
        $managementHierarchy = $this->managementHierarchyRepository->createBranch([
            "company_id" => $event->data->id,
            "name" => $event->data->name,
            'manager_id' => null,
            "phone" => $event->data->phone,
            "email" => $event->data->email,
            "type" => "branch",
            "is_first_branch" => 1,
            "is_main" => 1
        ], [
            "company_id" => $event->data->id,
            "country_id" => $event->data->country_id
        ]);
        $this->defaultConstraintService->createForBranch($managementHierarchy);


    }
}

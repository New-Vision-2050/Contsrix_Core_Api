<?php

namespace Modules\Company\ManagementHierarchy\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\Company\ManagementHierarchy\Events\CompanyCreatedEvent;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;
use Ramsey\Uuid\Uuid;

class CreateHierarchyListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(private ManagementHierarchyRepository $managementHierarchyRepository)
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
        $this->managementHierarchyRepository->createBranch([
            "company_id" => $event->data->id,
            "name" => $event->data->name,
            'manager_id' => null,
            "phone" => $event->data->phone,
            "email" => $event->data->email,
            "type" => "branch",
            "is_first_branch" => 1
        ], [
            "company_id" => $event->data->id,
            "country_id" => $event->data->country_id
        ]);
    }
}

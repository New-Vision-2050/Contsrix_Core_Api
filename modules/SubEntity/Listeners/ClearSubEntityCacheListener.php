<?php

declare(strict_types=1);

namespace Modules\SubEntity\Listeners;

use Modules\CompanyUser\Events\CompanyUserCreated;
use Modules\SubEntity\Services\SubEntityRecordsService;

class ClearSubEntityCacheListener
{
    protected SubEntityRecordsService $subEntityRecordsService;

    public function __construct(SubEntityRecordsService $subEntityRecordsService)
    {
        $this->subEntityRecordsService = $subEntityRecordsService;
    }

    public function handle(CompanyUserCreated $event): void
    {
        // Clear all sub-entity caches as a simple invalidation strategy.
        // For a more granular approach, you might extract sub_entity_id and registration_form_id
        // from the event data if they are available.
        $this->subEntityRecordsService->clearCache();
    }
}

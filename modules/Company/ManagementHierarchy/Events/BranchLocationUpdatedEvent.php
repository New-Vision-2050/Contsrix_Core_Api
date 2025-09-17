<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BranchLocationUpdatedEvent
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param int $branchId
     * @param string|null $latitude
     * @param string|null $longitude
     */
    public function __construct(
        public readonly int $branchId,
        public readonly ?string $latitude,
        public readonly ?string $longitude
    ) {}
}

<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Company\CompanyCore\Models\CompanyAddress;

class CompanyAddressUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var CompanyAddress
     */
    public CompanyAddress $companyAddress;

    /**
     * Create a new event instance.
     *
     * @param CompanyAddress $companyAddress
     */
    public function __construct(CompanyAddress $companyAddress)
    {
        $this->companyAddress = $companyAddress;
    }
}

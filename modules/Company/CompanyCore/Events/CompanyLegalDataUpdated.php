<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Company\CompanyCore\Models\CompanyLegalData;

class CompanyLegalDataUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * The CompanyLegalData instance.
     *
     * @var CompanyLegalData
     */
    public CompanyLegalData $legalData;

    /**
     * Create a new event instance.
     *
     * @param CompanyLegalData $legalData
     */
    public function __construct(CompanyLegalData $legalData)
    {
        $this->legalData = $legalData;
    }
}
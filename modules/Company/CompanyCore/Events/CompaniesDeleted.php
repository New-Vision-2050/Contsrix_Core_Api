<?php

namespace Modules\Company\CompanyCore\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldBeQueued;

class CompaniesDeleted
{
    use Dispatchable, SerializesModels;

    public array $ids;
    public function __construct($ids)
    {
        $this->ids = $ids->toArray();
    }
}

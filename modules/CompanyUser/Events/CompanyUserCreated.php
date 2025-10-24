<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\CompanyUser\Models\CompanyUser;

class CompanyUserCreated
{
    use Dispatchable, SerializesModels;

    public CompanyUser $companyUser;

    public function __construct(CompanyUser $companyUser)
    {
        $this->companyUser = $companyUser;
    }
}

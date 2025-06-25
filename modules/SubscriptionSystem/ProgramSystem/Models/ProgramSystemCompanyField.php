<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\ProgramSystem\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use BasePackage\Shared\Traits\UuidTrait;

class ProgramSystemCompanyField extends Pivot
{
    use UuidTrait;

    protected $table = 'program_system_company_field';
    public $incrementing = false;
    protected $keyType = 'string';
}

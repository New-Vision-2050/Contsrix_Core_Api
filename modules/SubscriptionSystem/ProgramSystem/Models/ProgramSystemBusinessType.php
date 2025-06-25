<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\ProgramSystem\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use BasePackage\Shared\Traits\UuidTrait;

class ProgramSystemBusinessType extends Pivot
{
    use UuidTrait;

    protected $table = 'program_system_business_types';
    public $incrementing = false;
    protected $keyType = 'string';
}

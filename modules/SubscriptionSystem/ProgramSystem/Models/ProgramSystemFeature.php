<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\ProgramSystem\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\SubscriptionSystem\Feature\Models\Feature;
use Modules\SubscriptionSystem\ProgramSystem\Database\factories\ProgramSystemFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Relations\Pivot;


class ProgramSystemFeature extends Pivot
{
    use UuidTrait;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'program_system_feature';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

}
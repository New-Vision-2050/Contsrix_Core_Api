<?php

declare(strict_types=1);

namespace Modules\Advance\AdvancePolicy\Models;

use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class AdvancePolicy extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'company_id',
        'max_amount',
        'max_installments',
        'max_active_advances',
        'requires_attachment',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'max_amount' => 'decimal:2',
        'max_installments' => 'integer',
        'max_active_advances' => 'integer',
        'requires_attachment' => 'boolean',
    ];
}

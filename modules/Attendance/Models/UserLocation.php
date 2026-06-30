<?php

declare(strict_types=1);

namespace Modules\Attendance\Models;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

class UserLocation extends Model
{
    use UuidTrait;
    use CustomBelongsToTenant;

    protected $table = 'user_locations';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'company_id',
        'latitude',
        'longitude',
        'accuracy',
        'location_source',
        'recorded_at',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'company_id' => 'string',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'accuracy' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withoutGlobalScopes();
    }
}

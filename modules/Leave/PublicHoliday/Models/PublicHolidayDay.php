<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicHolidayDay extends Model
{
    use UuidTrait;

    protected $table = 'public_holiday_days';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'public_holiday_id',
        'date',
        'is_compensation',
    ];

    protected $casts = [
        'id' => 'string',
        'public_holiday_id' => 'string',
        'date' => 'date',
        'is_compensation' => 'boolean',
    ];

    public function publicHoliday(): BelongsTo
    {
        return $this->belongsTo(PublicHoliday::class, 'public_holiday_id');
    }
}

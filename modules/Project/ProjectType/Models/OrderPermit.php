<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderPermit extends Model
{
    protected $table = 'order_permit';

    protected $fillable = [
        'code',
        'description',
        'type',
        'uds_period',
        'order_permit_department_id',
        'order_permit_type_id',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(OrderPermitDepartment::class, 'order_permit_department_id');
    }

    public function orderPermitType(): BelongsTo
    {
        return $this->belongsTo(OrderPermitType::class, 'order_permit_type_id');
    }

    public function udsRecords(): HasMany
    {
        return $this->hasMany(ProjectOrderPermitUds::class, 'type_code', 'code');
    }

}

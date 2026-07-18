<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPermit extends Model
{
    protected $table = 'order_permit';

    protected $fillable = [
        'code',
        'description',
        'type',
        'uds_period',
        'order_permit_department_id',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(OrderPermitDepartment::class, 'order_permit_department_id');
    }

}

<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderPermitDepartment extends Model
{
    protected $table = 'order_permit_department';

    protected $fillable = [
        'name',
    ];

    public function orderPermits(): HasMany
    {
        return $this->hasMany(OrderPermit::class, 'order_permit_department_id');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(ProjectWorkOrder::class, 'order_permit_department_id');
    }
}

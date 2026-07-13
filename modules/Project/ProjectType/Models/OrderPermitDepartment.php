<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderPermitDepartment extends Model
{
    protected $table = 'order_permit_department';

    protected $fillable = [
        'project_type_id',
        'order_permit_id',
        'code',
        'description',
    ];

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }

    public function orderPermit(): BelongsTo
    {
        return $this->belongsTo(OrderPermit::class, 'order_permit_id');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(ProjectWorkOrder::class, 'order_permit_department_id');
    }
}

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
        'project_type_id',
        'code',
        'description',
        'type',
    ];

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }

    public function departments(): HasMany
    {
        return $this->hasMany(OrderPermitDepartment::class, 'order_permit_id');
    }
}

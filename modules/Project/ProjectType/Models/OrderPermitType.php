<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderPermitType extends Model
{
    protected $table = 'order_permit_types';

    protected $fillable = [
        'name',
    ];

    public function orderPermits(): HasMany
    {
        return $this->hasMany(OrderPermit::class, 'order_permit_type_id');
    }
}

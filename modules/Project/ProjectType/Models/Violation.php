<?php

namespace Modules\Project\ProjectType\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Violation extends Model
{
    use UuidTrait;

    protected $table = 'violations';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'description',
        'category',
        'default_weight',
    ];

    protected $casts = [
        'default_weight' => 'decimal:2',
    ];

    public function safetyRecords(): BelongsToMany
    {
        return $this->belongsToMany(
            SafetyRecord::class,
            'safety_record_violation',
            'violation_id',
            'safety_record_id'
        )
        ->withPivot('weight', 'status')
        ->withTimestamps();
    }
}

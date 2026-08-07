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
        'work_cancellation',
        'work_stop',
        'equipment_exclusion',
    ];

    protected $casts = [
        'default_weight' => 'decimal:2',
        'work_cancellation' => 'boolean',
        'work_stop' => 'boolean',
        'equipment_exclusion' => 'boolean',
    ];

    /**
     * Arabic labels for enabled action flags, in catalog column order.
     *
     * @return list<string>
     */
    public function actions(): array
    {
        $actions = [];

        if ($this->work_cancellation) {
            $actions[] = 'إلغاء العمل';
        }

        if ($this->work_stop) {
            $actions[] = 'إيقاف العمل';
        }

        if ($this->equipment_exclusion) {
            $actions[] = 'استبعاد المعدة أو الموظف';
        }

        return $actions;
    }

    public function safetyRecords(): BelongsToMany
    {
        return $this->belongsToMany(
            SafetyRecord::class,
            'safety_record_violation',
            'violation_id',
            'safety_record_id'
        )
            ->using(SafetyRecordViolation::class)
            ->withPivot('id', 'weight', 'status')
            ->withTimestamps();
    }
}

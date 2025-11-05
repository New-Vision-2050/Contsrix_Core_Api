<?php

declare(strict_types=1);

namespace Modules\Attendance\Models;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class AppliedAttendanceConstraint extends Model
{
    // Remove UuidTrait since this table uses auto-incrementing IDs
    use BaseFilterable;
    // use SoftDeletes;
    use CustomBelongsToTenant;

    protected $table = 'applied_attendance_constraints';

    protected $fillable = [
        'attendance_id',
        'constraint_snapshot',
        'company_id',
    ];

    protected $casts = [
        // ID is auto-incrementing integer, no need to cast
        'constraint_snapshot' => 'array',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];
    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'attendance_id', 'id');
    }
}

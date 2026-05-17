<?php

declare(strict_types=1);

namespace Modules\Attendance\Models;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

/**
 * @property string $id
 * @property string $attendance_constraint_id
 * @property string $company_id
 * @property string|null $name
 * @property float $latitude
 * @property float $longitude
 * @property int $radius
 * @property string|null $created_by
 */
class AttendanceConstraintLocation extends Model
{
    use UuidTrait;
    use CustomBelongsToTenant;

    protected $table = 'attendance_constraint_locations';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'attendance_constraint_id',
        'company_id',
        'name',
        'latitude',
        'longitude',
        'radius',
        'created_by',
    ];

    protected $casts = [
        'id' => 'string',
        'attendance_constraint_id' => 'string',
        'company_id' => 'string',
        'latitude' => 'float',
        'longitude' => 'float',
        'radius' => 'integer',
    ];

    public function constraint(): BelongsTo
    {
        return $this->belongsTo(AttendanceConstraint::class, 'attendance_constraint_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

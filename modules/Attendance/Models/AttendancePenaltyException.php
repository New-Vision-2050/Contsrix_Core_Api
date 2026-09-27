<?php

declare(strict_types=1);

namespace Modules\Attendance\Models;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

/**
 * One employee decision on a rule-based auto clock-out penalty (day exception).
 *
 * @property string $id
 * @property string $user_id
 * @property string $company_id
 * @property string $attendance_id
 * @property string $business_date
 * @property string $action  waived | accepted
 * @property int $penalty_minutes
 * @property string|null $original_clock_out_time
 * @property string|null $restored_clock_out_time
 * @property \Carbon\Carbon $decided_at
 */
class AttendancePenaltyException extends Model
{
    use UuidTrait;
    use CustomBelongsToTenant;

    public const ACTION_WAIVED   = 'waived';
    public const ACTION_ACCEPTED = 'accepted';

    protected $table = 'attendance_penalty_exceptions';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'company_id',
        'attendance_id',
        'business_date',
        'action',
        'penalty_minutes',
        'original_clock_out_time',
        'restored_clock_out_time',
        'decided_at',
    ];

    protected $casts = [
        'id'            => 'string',
        'user_id'       => 'string',
        'company_id'    => 'string',
        'attendance_id' => 'string',
        'penalty_minutes' => 'integer',
        'decided_at'    => 'datetime',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

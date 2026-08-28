<?php

declare(strict_types=1);

namespace Modules\Attendance\Models;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

class UserManualAttendanceOverride extends Model
{
    use UuidTrait;
    use CustomBelongsToTenant;

    protected $table = 'user_manual_attendance_overrides';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'company_id',
        'status',
        'starts_on',
        'ends_on',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'company_id' => 'string',
        'starts_on' => 'date',
        'ends_on' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withoutGlobalScopes();
    }
}

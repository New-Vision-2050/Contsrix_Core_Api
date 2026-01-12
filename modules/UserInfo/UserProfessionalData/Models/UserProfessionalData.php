<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserProfessionalData\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\UserInfo\UserProfessionalData\Database\factories\UserProfessionalDataFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Country\Models\Country;
use Modules\JobTitle\Models\JobTitle;
use Modules\Shared\JobType\Models\JobType;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Modules\User\Models\User;

class UserProfessionalData extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use SoftDeletes;
    use BelongsToTenant;

    //public array $translatable = [];
    protected $table = 'user_professional_datas';
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'global_id',
        'user_id',
        'branch_id',
        'management_id',
        'department_id',
        'job_type_id',
        'job_title_id',
        'job_code',
        'attendance_constraint_id'
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): UserProfessionalDataFactory
    {
        return UserProfessionalDataFactory::new();
    }
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function branch()
    {
        return $this->belongsTo(ManagementHierarchy::class,'branch_id');
    }

    public function management()
    {
        return $this->belongsTo(ManagementHierarchy::class,'management_id');
    }


    public function department()
    {
        return $this->belongsTo(ManagementHierarchy::class,'department_id');
    }

    public function jobType()
    {
        return $this->belongsTo(JobType::class);
    }
    public function attendanceConstraint()
    {
        return $this->belongsTo(AttendanceConstraint::class);
    }

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class)->withoutGlobalScope("active");
    }

    public function user()//TODO under Testing not used up till now
    {
        return $this->belongsTo(User::class, 'global_id', 'global_company_user_id')
            ->where('users.company_id', '=', $this->company_id);
    }


}

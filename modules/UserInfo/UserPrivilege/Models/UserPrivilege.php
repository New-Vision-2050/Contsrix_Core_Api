<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\UserInfo\UserPrivilege\Database\factories\UserPrivilegeFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Shared\Period\Models\Period;
use Modules\Shared\Privilege\Models\Privilege;
use Modules\Shared\TypeAllowance\Models\TypeAllowance;
use Modules\Shared\TypePrivilege\Models\TypePrivilege;
use Modules\MedicalInsurance\Models\MedicalInsurance;

//use BasePackage\Shared\Traits\HasTranslations;

class UserPrivilege extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'global_id',
        'type_privilege_id',
        'type_allowance_code',
        'charge_amount',
        'description',
        'privilege_id',
        'period_id',
        'medical_insurance_id',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): UserPrivilegeFactory
    {
        return UserPrivilegeFactory::new();
    }
    public function privilege()
    {
        return $this->belongsTo(Privilege::class);
    }

    public function typePrivilege()
    {
        return $this->belongsTo(TypePrivilege::class);
    }


    public function typeAllowance()
    {
        return $this->belongsTo(TypeAllowance::class,'type_allowance_code','code');
    }

    public function period()
    {
        return $this->belongsTo(Period::class);
    }

    public function medicalInsurance()
    {
        return $this->belongsTo(MedicalInsurance::class, 'medical_insurance_id');
    }
}

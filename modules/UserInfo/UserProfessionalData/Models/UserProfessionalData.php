<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserProfessionalData\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\UserInfo\UserProfessionalData\Database\factories\UserProfessionalDataFactory;
use BasePackage\Shared\Traits\BaseFilterable;
//use BasePackage\Shared\Traits\HasTranslations;

class UserProfessionalData extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];
    protected $table = 'user_professional_datas';
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'global_id',
        'branch_id',
        'management_id',
        'department_id',
        'job_type_id',
        'job_title_id',
        'job_code',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): UserProfessionalDataFactory
    {
        return UserProfessionalDataFactory::new();
    }
}

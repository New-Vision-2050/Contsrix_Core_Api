<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\UserInfo\UserPrivilege\Database\factories\UserPrivilegeFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Shared\Privilege\Models\Privilege;

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
        'type_privilege',
        'type_allowance',
        'rate',
        'description',
        'privilege_id',
        'period',
        'insurance_company',
        'insurance_number',
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
}

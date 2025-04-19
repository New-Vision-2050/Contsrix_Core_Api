<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserSalary\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\UserInfo\UserSalary\Database\factories\UserSalaryFactory;
use BasePackage\Shared\Traits\BaseFilterable;
//use BasePackage\Shared\Traits\HasTranslations;

class UserSalary extends Model
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
        'basic',
        'salary',
        'type',
        'description',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): UserSalaryFactory
    {
        return UserSalaryFactory::new();
    }
}

<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserSalary\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\UserInfo\UserSalary\Database\factories\UserSalaryFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Shared\SalaryType\Models\SalaryType;

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
        'hour_rate',
        'salary',
        'period_id',
        'description',
        'salary_type_code'
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): UserSalaryFactory
    {
        return UserSalaryFactory::new();
    }
    public function salaryType()
    {
        return $this->belongsTo(SalaryType::class,'salary_type_code','code');
    }
}

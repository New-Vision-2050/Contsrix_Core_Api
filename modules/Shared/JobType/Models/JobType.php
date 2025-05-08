<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\JobTitle\Models\JobTitle;
use Modules\Shared\JobType\Database\factories\JobTypeFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class JobType extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;
    use HasTranslations;
    //use SoftDeletes;

    public array $translatable = ['name'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'company_id',
        'status'
    ];

    protected $casts = [
        'id' => 'string',
    ];
    protected static function booted()
    {
        static::addGlobalScope("active",function ($query) {
            $query->where('status', 1);
        });
    }
    protected static function newFactory(): JobTypeFactory
    {
        return JobTypeFactory::new();
    }

    public function jobTitles()
    {
        return $this->hasMany(JobTitle::class, 'job_type_id');
    }

    public function userProfissional()
    {
        return $this->hasMany(UserProfessionalData::class);
    }
}

<?php

declare(strict_types=1);

namespace Modules\JobTitle\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\JobTitle\Database\factories\JobTitleFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use Modules\Shared\JobType\Models\JobType;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class JobTitle extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    use BelongsToTenant;

    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'type',
        "name",
        "job_type_id",
        "description",
        "status"
    ];
    public array $translatable = ['name'];
    protected $casts = [
        'id' => 'string',
    ];


    protected static function newFactory(): JobTitleFactory
    {
        return JobTitleFactory::new();
    }

    public function jobType()
    {
        return $this->belongsTo(JobType::class);
    }

    public function userProfissional()
    {
        return $this->hasMany(UserProfessionalData::class);
    }
}

<?php

declare(strict_types=1);

namespace Modules\JobTitle\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\JobTitle\Database\factories\JobTitleFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;

class JobTitle extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;

    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        // 'name',
    ];
    public array $translatable = ['name'];
    protected $casts = [
        'id' => 'string',
    ];

    protected static function booted()
    {
        static::addGlobalScope(function ($query) {
            if (!tenancy()->initialized) {
                return;
            }
            $query->when(tenant("is_central_company")== 1, function ($q) {
                $q->where("for_central_company",1);
            });
        });
    }

    protected static function newFactory(): JobTitleFactory
    {
        return JobTitleFactory::new();
    }
}

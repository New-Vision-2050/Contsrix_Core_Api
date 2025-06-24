<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\ProgramSystem\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\SubscriptionSystem\Feature\Models\Feature;
use Modules\SubscriptionSystem\ProgramSystem\Database\factories\ProgramSystemFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;

class ProgramSystem extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    //use SoftDeletes;

    public array $translatable = ['name'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
    ];


    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): ProgramSystemFactory
    {
        return ProgramSystemFactory::new();
    }

    public function features()
    {
        return $this->belongsToMany(
            Feature::class,
            'program_system_feature'
        )
        ->using(ProgramSystemFeature::class) 
        ->withPivot('module_id')->withTimestamps();
    }


}

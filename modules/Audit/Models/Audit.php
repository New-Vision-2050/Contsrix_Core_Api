<?php

declare(strict_types=1);

namespace Modules\Audit\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Audit\Database\factories\AuditFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\User\Models\User;

//use BasePackage\Shared\Traits\HasTranslations;

class Audit extends \OwenIt\Auditing\Models\Audit
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];
//    public $with = ['user'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function user()

    {
        return $this->belongsTo(User::class , "user_id","id")->withoutTenancy();
    }

    protected static function newFactory(): AuditFactory
    {
        return AuditFactory::new();
    }
}

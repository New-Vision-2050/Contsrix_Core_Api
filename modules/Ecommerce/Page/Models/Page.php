<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Page\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Ecommerce\Page\Database\factories\PageFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;

class Page extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    //use SoftDeletes;

    public array $translatable = ['description'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'description',
        'type',
        'company_id',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): PageFactory
    {
        return PageFactory::new();
    }
}

<?php

declare(strict_types=1);

namespace Modules\DocumentType\Models;

use BasePackage\Shared\Traits\BelongsToTenant;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DocumentType\Database\factories\DocumentTypeFactory;
use BasePackage\Shared\Traits\BaseFilterable;

class DocumentType extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use SoftDeletes;
    use BelongsToTenant;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'company_id',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): DocumentTypeFactory
    {
        return DocumentTypeFactory::new();
    }
}

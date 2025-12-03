<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactMessage\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\WebsiteCMS\WebsiteContactMessage\Database\factories\WebsiteContactMessageFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class WebsiteContactMessage extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'status',
        'message',
        'company_id',
    ];

    protected $casts = [
        'id' => 'string',
        'status' => 'integer',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    protected static function newFactory(): WebsiteContactMessageFactory
    {
        return WebsiteContactMessageFactory::new();
    }
}

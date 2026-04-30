<?php

declare(strict_types=1);

namespace Modules\Reports\Models;

use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Reports\Database\factories\ReportTemplateFactory;
use Modules\User\Models\User;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * @property string $id
 * @property string $company_id
 * @property string|null $created_by
 * @property string|array $name
 * @property string|array|null $description
 * @property array $config
 * @property bool $is_active
 */
class ReportTemplate extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    use BelongsToTenant;

    protected $table = 'report_templates';

    public $incrementing = false;

    protected $keyType = 'string';

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'company_id',
        'created_by',
        'name',
        'description',
        'config',
        'is_active',
    ];

    protected $casts = [
        'id'         => 'string',
        'company_id' => 'string',
        'created_by' => 'string',
        'config'     => 'array',
        'is_active'  => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'template_id');
    }

    protected static function newFactory(): ReportTemplateFactory
    {
        return ReportTemplateFactory::new();
    }
}

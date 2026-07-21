<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Project\ProjectManagement\Models\ProjectManagement as ProjectModel;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class UdsExcelSheet extends Model implements HasMedia
{
    use UuidTrait;
    use InteractsWithMedia;
    use BelongsToTenant;

    protected $table = 'uds_excel_sheets';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'project_id',
        'company_id',
    ];

    protected $casts = [
        'id' => 'string',
        'project_id' => 'string',
        'company_id' => 'string',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('uds_sheets');
    }

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectModel::class, 'project_id')->withoutGlobalScopes();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->withoutGlobalScopes();
    }
}

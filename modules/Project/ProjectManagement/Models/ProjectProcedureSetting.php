<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\Company\CompanyCore\Models\Company;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ProjectProcedureSetting extends Model
{
    use BelongsToTenant;
    use UuidTrait;

    public const PROCEDURE_TYPE = 'project_procedure';

    protected $table = 'project_procedure_settings';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'project_id',
        'procedure_setting_id',
        'attachment_type_id',
        'attachment_sub_type_id',
        'attachment_sub_sub_type_id',
        'job_attribute_id',
        'used_in_document_cycle',
        'appears_in_archive_after_approval',
        'appears_in_attachments_library',
        'requires_asset_id',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'project_id' => 'string',
        'procedure_setting_id' => 'string',
        'attachment_type_id' => 'string',
        'attachment_sub_type_id' => 'string',
        'attachment_sub_sub_type_id' => 'string',
        'job_attribute_id' => 'string',
        'used_in_document_cycle' => 'boolean',
        'appears_in_archive_after_approval' => 'boolean',
        'appears_in_attachments_library' => 'boolean',
        'requires_asset_id' => 'boolean',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->withoutGlobalScopes();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectManagement::class, 'project_id')->withoutGlobalScopes();
    }

    public function procedureSetting(): BelongsTo
    {
        return $this->belongsTo(ProcedureSetting::class, 'procedure_setting_id')->withoutGlobalScopes();
    }

    public function attachmentType(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'attachment_type_id')->withoutGlobalScopes();
    }

    public function attachmentSubType(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'attachment_sub_type_id')->withoutGlobalScopes();
    }

    public function attachmentSubSubType(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'attachment_sub_sub_type_id')->withoutGlobalScopes();
    }

    public function jobAttribute(): BelongsTo
    {
        return $this->belongsTo(ProjectProcedureJobAttribute::class, 'job_attribute_id');
    }

    public function receiverCompanies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'project_procedure_setting_receiver_companies', 'project_procedure_setting_id', 'company_id')
            ->withoutGlobalScopes();
    }
}

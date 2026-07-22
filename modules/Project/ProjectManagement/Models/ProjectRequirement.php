<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Company\CompanyCore\Models\Company;
use Modules\DocumentType\Models\DocumentType;
use Modules\Project\ProjectManagement\Database\factories\ProjectRequirementFactory;
use Modules\Shared\AcademicSpecialization\Models\AcademicSpecialization;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ProjectRequirement extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use UuidTrait;

    protected $table = 'project_requirements';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'project_id',
        'requirement_code',
        'required_document_name',
        'document',
        'document_type_id',
        'document_type',
        'specialization_id',
        'specialization',
        'stage',
        'sending_entity_id',
        'sending_entity',
        'review_entity_id',
        'review_entity',
        'repetition',
        'repetition_interval_type',
        'repeat_days',
        'evaluation_status',
        'resulting_document',
        'completion_percentage',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'project_id' => 'string',
        'document_type_id' => 'string',
        'specialization_id' => 'string',
        'sending_entity_id' => 'string',
        'review_entity_id' => 'string',
        'repeat_days' => 'array',
        'completion_percentage' => 'integer',
    ];

    protected static function newFactory(): ProjectRequirementFactory
    {
        return ProjectRequirementFactory::new();
    }

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

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id')->withoutGlobalScopes();
    }

    public function specializationLookup(): BelongsTo
    {
        return $this->belongsTo(AcademicSpecialization::class, 'specialization_id')->withoutGlobalScopes();
    }

    public function sendingEntityCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'sending_entity_id')->withoutGlobalScopes();
    }

    public function reviewEntityCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'review_entity_id')->withoutGlobalScopes();
    }

    public function receiverCompanies(): BelongsToMany
    {
        return $this->belongsToMany(
            Company::class,
            'project_requirement_receiver_companies',
            'project_requirement_id',
            'company_id'
        )->withoutGlobalScopes()->withTimestamps();
    }
}

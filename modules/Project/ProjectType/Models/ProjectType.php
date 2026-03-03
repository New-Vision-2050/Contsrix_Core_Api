<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Project\ProjectType\Database\factories\ProjectTypeFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use App\Traits\CustomBelongsToTenant;
use Modules\Company\CompanyCore\Models\Company;
use Nevadskiy\Tree\AsTree;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ProjectType extends Model
{
    use HasFactory;
    use BaseFilterable;
    use AsTree;
    use BelongsToTenant;

    protected $table = "project_types";

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'icon',
        'parent_id',
        'reference_project_type_id',
        'company_id',
        'path',
        'is_created',
        'is_have_schema',
        'is_active',
    ];

    protected $casts = [
        'is_created' => 'int',
        'is_have_schema' => 'int',
        'is_active' => 'int',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function parent()
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function referenceProjectType()
    {
        return $this->belongsTo(ProjectType::class, 'reference_project_type_id');
    }

    public function schemas()
    {
        return $this->belongsToMany(
            Schema::class,
            'project_type_schemas',
            'project_type_id',
            'schema_id'
        )->withTimestamps();
    }

    public function projectDataSetting()
    {
        return $this->hasOne(ProjectDataSetting::class, 'project_type_id');
    }

    public function attachmentContractSetting()
    {
        return $this->hasOne(AttachmentContractSetting::class, 'project_type_id');
    }

    public function attachmentTermsContractSetting()
    {
        return $this->hasOne(AttachmentTermsContractSetting::class, 'project_type_id');
    }

    public function contractorContractSetting()
    {
        return $this->hasOne(ContractorContractSetting::class, 'project_type_id');
    }

    public function employeeContractSetting()
    {
        return $this->hasOne(EmployeeContractSetting::class, 'project_type_id');
    }

    public function departmentContractSetting()
    {
        return $this->hasOne(DepartmentContractSetting::class, 'project_type_id');
    }

    public function getRelationshipToPrimaryModel(): string
    {
        return "company";
    }

    public function scopeSecondLevel($query)
    {
        return $query->whereHas('parent', function ($q) {
            $q->whereNull('parent_id');
        });
    }

    public function scopeSeeded($query)
    {
        return $query->where('is_created', false);
    }

    public function scopeUserCreated($query)
    {
        return $query->where('is_created', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithSchema($query)
    {
        return $query->where('is_have_schema', true);
    }

    public function isSeeded(): bool
    {
        return !$this->is_created;
    }

    public function hasSchema(): bool
    {
        return $this->is_have_schema;
    }

    protected static function newFactory(): ProjectTypeFactory
    {
        return ProjectTypeFactory::new();
    }
}

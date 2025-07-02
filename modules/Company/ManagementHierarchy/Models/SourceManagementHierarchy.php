<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Company\CompanyCore\Models\Company;
use Modules\JobTitle\Models\JobTitle;
use Modules\Shared\JobType\Models\JobType;
use Modules\User\Models\User;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class SourceManagementHierarchy extends Model
{
    use HasFactory;


    protected $table = 'source_management_hierarchies';

    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        "name",
        "type",
        "company_id",
        "is_active",
    ];



    /**
     * Get the management hierarchy that this management unit belongs to.
     */
    public function details()
    {
        return $this->hasMany(ManagementHierarchyDetail::class, 'reference_department_id', 'id');
    }

    /**
     * Get management hierarchies through management details
     */
    public function managementHierarchies()
    {
        return $this->hasManyThrough(
            ManagementHierarchy::class,
            ManagementHierarchyDetail::class,
            'reference_department_id', // Foreign key on management_hierarchy_details table
            'id', // Foreign key on management_hierarchies table
            'id', // Local key on source_management_hierarchies table
            'management_hierarchy_id' // Local key on management_hierarchy_details table
        );
    }



    /**
     * Get the company that this management unit belongs to.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }


    /**
     * The job types that belong to the management hierarchy.
     */
    public function jobTypes()
    {
        return $this->belongsToMany(
            JobType::class,
            'management_hierarchy_job_types',
            'source_management_hierarchy_id',
            'job_type_id'
        )->withTimestamps();
    }

    /**
     * The job titles that belong to the management hierarchy.
     */
    public function jobTitles()
    {
        return $this->belongsToMany(
            JobTitle::class,
            'management_hierarchy_job_titles',
            'source_management_hierarchy_id',
            'job_title_id'
        )->withTimestamps();
    }

    /**
     * The branches that belong to the management hierarchy.
     */
    public function relatedBranches()
    {
        return $this->belongsToMany(
            ManagementHierarchy::class,
            'management_hierarchy_branches',
            'source_management_hierarchy_id',
            'branch_id'//management_hierachies
        )->withTimestamps();
    }



    /**
     * The managements that belong to the management hierarchy.
     */
    public function relatedManagements()
    {
        return $this->belongsToMany(
            ManagementHierarchy::class,
            'management_hierarchy_managements',
            'source_management_hierarchy_id',
            'management_id'//management_hierachies
        )->withTimestamps();
    }





}

<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Models;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Models\CompanyAddress;
use Modules\User\Models\User;

class Branch extends Model
{
    use HasFactory;
    use BaseFilterable;
    use CustomBelongsToTenant;

    protected $table = "branches";

    protected $fillable = [
        'name',
        'parent_id',
        'company_id',
        'path',
        'manager_id',
        'phone',
        'phone_code',
        'email',
        'latitude',
        'longitude',
        'is_first_branch',
        'is_main',
        'is_active'
    ];



    /**
     * Get the corresponding ManagementHierarchy record (morph relationship).
     */
    public function managementHierarchy()
    {
        return $this->morphOne(ManagementHierarchy::class, 'manageable');
    }

    /**
     * Get the company this branch belongs to.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the manager of this branch.
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the address for this branch.
     */
    public function address()
    {
        return $this->hasOne(CompanyAddress::class, 'branch_id');
    }

    /**
     * Get the parent branch.
     */
    public function parent()
    {
        return $this->belongsTo(Branch::class, 'parent_id');
    }

    /**
     * Get the child branches.
     */
    public function children()
    {
        return $this->hasMany(Branch::class, 'parent_id');
    }

    /**
     * Scope for active branches.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for main branches.
     */
    public function scopeMain($query)
    {
        return $query->where('is_main', true);
    }

    /**
     * Scope for first branches.
     */
    public function scopeFirst($query)
    {
        return $query->where('is_first_branch', true);
    }
}

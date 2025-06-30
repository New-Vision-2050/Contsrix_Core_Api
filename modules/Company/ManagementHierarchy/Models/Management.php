<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Models;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Company\CompanyCore\Models\Company;
use Modules\User\Models\User;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchyDetail;

class Management extends Model
{
    use HasFactory;
    use BaseFilterable;
    use CustomBelongsToTenant;

    protected $table = "managements";

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
     * Get the management detail.
     */
    public function detail()
    {
        return $this->hasOne(ManagementHierarchyDetail::class, 'management_id');
    }

    /**
     * Get the company this management belongs to.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the manager of this management.
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the parent management.
     */
    public function parent()
    {
        return $this->belongsTo(Management::class, 'parent_id');
    }

    /**
     * Get the child managements.
     */
    public function children()
    {
        return $this->hasMany(Management::class, 'parent_id');
    }

    /**
     * Scope for active managements.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for main managements.
     */
    public function scopeMain($query)
    {
        return $query->where('is_main', true);
    }
}

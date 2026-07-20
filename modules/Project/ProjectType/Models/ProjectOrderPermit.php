<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Project\ProjectManagement\Models\ProjectContractor;
use Modules\Project\ProjectManagement\Models\ProjectManagement as ProjectModel;
use Modules\Country\Models\State;
use Modules\Project\ProjectManagement\Models\Contractor;

class ProjectOrderPermit extends Model
{
    protected $table = 'project_order_permit';

    protected $fillable = [
        'project_id',
        'project_management_id',
        'projects_district_id',
        'order_permit_id',
        'order_permit_department_id',
        'contractor_id',
        'name',
        'type',
        'assigned_date',
        'state_id',
        'lat',
        'long',
        'price',
        'executing_entity',
        'office',
        'consultant_current_basket',
        'consultant_assignment_date',
        'consultant_last_procedure_code',
        'consultant_last_procedure_date',
        'consultant_column_155_entry_date',
        'contractor_last_procedure_code',
        'contractor_last_procedure_date',
        'contractor_column_155_entry_date',
        'material_balance_elec_contractor',
        'contractor_work_order_status',
        'contractor_basket',
        'consultant_price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'consultant_price' => 'decimal:2',
        'assigned_date' => 'date',
        'consultant_assignment_date' => 'date',
        'consultant_last_procedure_date' => 'date',
        'consultant_column_155_entry_date' => 'date',
        'contractor_last_procedure_date' => 'date',
        'contractor_column_155_entry_date' => 'date',
        'last_row_update_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectModel::class, 'project_id');
    }

    public function projectManagement(): BelongsTo
    {
        return $this->belongsTo(ProjectManagement::class, 'project_management_id');
    }

    public function projectDistrict(): BelongsTo
    {
        return $this->belongsTo(ProjectDistrict::class, 'projects_district_id');
    }

    public function orderPermit(): BelongsTo
    {
        return $this->belongsTo(OrderPermit::class, 'order_permit_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(OrderPermitDepartment::class, 'order_permit_department_id');
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(ProjectContractor::class, 'contractor_id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }
}

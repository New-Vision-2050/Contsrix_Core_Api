<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
        'import_log',
        'name',
        'type',
        'assigned_date',
        'state_id',
        'project_completion_phase_id',
        'project_phase_status_id',
        'connection_completion_phase_id',
        'connection_phase_status_id',
        'start_permit_date',
        'end_permit_date',
        'note_from_permit_to_departments',
        'note_from_departments_to_permit',
        'is_taked_action',
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
        'employee_id',
        'target_drilling',
        'achieved_drilling',
        'target_extention',
        'achieved_extention',
        'description_details',
        'consultant_statement',
        'last_date_consultant_statement',
        'consultnat_statement_status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'consultant_price' => 'decimal:2',
        'is_taked_action' => 'boolean',
        'assigned_date' => 'date',
        'start_permit_date' => 'date',
        'end_permit_date' => 'date',
        'consultant_assignment_date' => 'date',
        'consultant_last_procedure_date' => 'date',
        'consultant_column_155_entry_date' => 'date',
        'contractor_last_procedure_date' => 'date',
        'contractor_column_155_entry_date' => 'date',
        'last_row_update_at' => 'datetime',
        'target_drilling' => 'decimal:2',
        'achieved_drilling' => 'decimal:2',
        'target_extention' => 'decimal:2',
        'achieved_extention' => 'decimal:2',
        'last_date_consultant_statement' => 'date',
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

    public function projectCompletionPhase(): BelongsTo
    {
        return $this->belongsTo(ProjectCompletionPhase::class, 'project_completion_phase_id');
    }

    public function projectPhaseStatus(): BelongsTo
    {
        return $this->belongsTo(ProjectPhaseStatus::class, 'project_phase_status_id');
    }

    public function connectionCompletionPhase(): BelongsTo
    {
        return $this->belongsTo(ConnectionCompletionPhase::class, 'connection_completion_phase_id');
    }

    public function connectionPhaseStatus(): BelongsTo
    {
        return $this->belongsTo(ConnectionPhaseStatus::class, 'connection_phase_status_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\Modules\User\Models\User::class, 'employee_id');
    }

    public function safetyRecords(): MorphMany
    {
        return $this->morphMany(SafetyRecord::class, 'morphable');
    }

    public function noteLogs(): HasMany
    {
        return $this->hasMany(ProjectOrderPermitNoteLog::class, 'project_order_permit_id');
    }
}

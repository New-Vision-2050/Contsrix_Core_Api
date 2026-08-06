<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Project\ProjectManagement\Models\ProjectManagement as ProjectModel;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ProjectOrderPermitUds extends Model
{
    use UuidTrait;
    use BelongsToTenant;

    protected $table = 'project_order_permit_uds';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'project_id',
        'company_id',
        'name',
        'type_code',
        'executing_entity',
        'office',
        'contractor_basket',
        'consultant_current_basket',
        'assigned_date',
        'consultant_assignment_date',
        'contractor_last_procedure_code',
        'contractor_last_procedure_date',
        'contractor_column_155_entry_date',
        'consultant_last_procedure_code',
        'consultant_last_procedure_date',
        'consultant_column_155_entry_date',
        'material_balance_elec_contractor',
        'contractor_work_order_status',
        'price',
        'consultant_price',
        'penalty_amount',
        'finance_approval_date',
        'certificate_source_number',
        'modifier_employee_number',
        'contractor_assigned_employee_number',
        'work_order_status',
        'work_order_situation',
        'penalty_percentage',
        'delay_duration',
        'disbursement_status',
        'total_cost',
        'indirect_cost',
        'labor_cost',
        'consumed_material_cost',
        'office_code',
        'current_entity',
        'cost_center_name',
        'cost_center',
        'extract_number',
        'completion_certificate_amount',
        'contractor_approval_cert_date',
        'certificate_approval_date',
        'certificate_date',
        'procedure_203_date',
        'last_procedure_name',
        'work_order_type',
        'contract_number',
        'contractor_name',
        'subscriber_type',
    ];

    protected $casts = [
        'id' => 'string',
        'project_id' => 'string',
        'company_id' => 'string',
        'price' => 'decimal:2',
        'consultant_price' => 'decimal:2',
        'assigned_date' => 'date',
        'consultant_assignment_date' => 'date',
        'contractor_last_procedure_date' => 'date',
        'contractor_column_155_entry_date' => 'date',
        'consultant_last_procedure_date' => 'date',
        'consultant_column_155_entry_date' => 'date',
        'finance_approval_date' => 'date',
        'contractor_approval_cert_date' => 'date',
        'certificate_approval_date' => 'date',
        'certificate_date' => 'date',
        'procedure_203_date' => 'date',
    ];

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

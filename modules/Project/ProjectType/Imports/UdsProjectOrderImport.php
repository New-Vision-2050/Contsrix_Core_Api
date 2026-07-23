<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Ramsey\Uuid\Uuid;
use Carbon\Carbon;

class UdsProjectOrderImport implements ToCollection, WithChunkReading
{
    private string $projectId;
    private string $companyId;

    public function __construct(string $projectId, string $companyId)
    {
        $this->projectId = $projectId;
        $this->companyId = $companyId;
    }

    public function collection(Collection $rows): void
    {
        $batch = [];
        $now = now()->toDateTimeString();

        foreach ($rows as $row) {
            $rowArray = $row->toArray();
            $name = trim((string) ($rowArray[34] ?? ''));
            if ($name === '') continue;

            $batch[] = [
                'id' => (string) Uuid::uuid4(),
                'project_id' => $this->projectId,
                'company_id' => $this->companyId,
                'name' => $name,
                'type_code' => $this->value($rowArray, 35),

                'executing_entity' => $this->value($rowArray, 27),
                'office' => $this->value($rowArray, 37),
                'contractor_basket' => $this->value($rowArray, 16),
                'consultant_current_basket' => $this->value($rowArray, 16),
                'assigned_date' => $this->parseDate($rowArray, 25),
                'consultant_assignment_date' => $this->parseDate($rowArray, 25),
                'contractor_last_procedure_code' => $this->value($rowArray, 30),
                'contractor_last_procedure_date' => $this->parseDate($rowArray, 28),
                'contractor_column_155_entry_date' => $this->parseDate($rowArray, 24),
                'consultant_last_procedure_code' => $this->value($rowArray, 30),
                'consultant_last_procedure_date' => $this->parseDate($rowArray, 28),
                'consultant_column_155_entry_date' => $this->parseDate($rowArray, 24),
                'material_balance_elec_contractor' => $this->value($rowArray, 13),
                'contractor_work_order_status' => $this->value($rowArray, 6),
                'price' => $this->parseFloat($rowArray, 31),
                'consultant_price' => $this->parseFloat($rowArray, 31),
                'contractor_name' => $this->value($rowArray, 36),

                'penalty_amount' => $this->parseFloat($rowArray, 0),
                'finance_approval_date' => $this->parseDate($rowArray, 1),
                'certificate_source_number' => $this->value($rowArray, 2),
                'modifier_employee_number' => $this->value($rowArray, 3),
                'contractor_assigned_employee_number' => $this->value($rowArray, 4),
                'work_order_status' => $this->value($rowArray, 5),
                'work_order_situation' => $this->value($rowArray, 6),
                'penalty_percentage' => $this->parseFloat($rowArray, 7),
                'delay_duration' => $this->value($rowArray, 8),
                'disbursement_status' => $this->value($rowArray, 9),
                'total_cost' => $this->parseFloat($rowArray, 10),
                'indirect_cost' => $this->parseFloat($rowArray, 11),
                'labor_cost' => $this->parseFloat($rowArray, 12),
                'unconsumed_material_cost' => $this->parseFloat($rowArray, 13),
                'consumed_material_cost' => $this->parseFloat($rowArray, 14),
                'office_code' => $this->value($rowArray, 15),
                'current_entity' => $this->value($rowArray, 16),
                'cost_center_name' => $this->value($rowArray, 17),
                'cost_center' => $this->value($rowArray, 18),
                'extract_number' => $this->value($rowArray, 19),
                'completion_certificate_amount' => $this->parseFloat($rowArray, 20),
                'contractor_approval_cert_date' => $this->parseDate($rowArray, 21),
                'certificate_approval_date' => $this->parseDate($rowArray, 22),
                'certificate_date' => $this->parseDate($rowArray, 23),
                'receipt_from_contractor_date' => $this->parseDate($rowArray, 24),
                'delivery_to_contractor_date' => $this->parseDate($rowArray, 25),
                'procedure_203_date' => $this->parseDate($rowArray, 26),
                'last_procedure_name' => $this->value($rowArray, 29),
                'work_order_type' => $this->value($rowArray, 33),
                'contract_number' => $this->value($rowArray, 32),
                'subscriber_type' => $this->value($rowArray, 34), // AJ
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($batch)) {
            DB::table('uds_project_order_permit')->insert($batch);
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }

    private function value(array $row, int $index): ?string
    {
        $val = trim((string) ($row[$index] ?? ''));
        return $val !== '' ? $val : null;
    }

    private function parseDate(array $row, int $index): ?string
    {
        $val = $this->value($row, $index);
        if ($val === null) return null;
        try {
            return Carbon::parse($val)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseFloat(array $row, int $index): ?float
    {
        $val = $this->value($row, $index);
        return $val !== null ? (float) $val : null;
    }
}

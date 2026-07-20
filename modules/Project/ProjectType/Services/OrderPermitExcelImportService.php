<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Modules\Project\ProjectType\Models\OrderPermit;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;

class OrderPermitExcelImportService
{

    public function importFromExcelRows(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

              $orderPermits = OrderPermit::all(['id', 'code', 'type']);

        $codeToOrderPermitId = [];
        $typeToOrderPermitId = [];

        foreach ($orderPermits as $op) {
            if ($op->code !== null) {
                $codeToOrderPermitId[(string) $op->code] = $op->id;
            }
            if ($op->type !== null) {
                $typeToOrderPermitId[(string) $op->type] = $op->id;
            }
        }


        $workOrderNumbers = [];
        foreach ($rows as $row) {
            $num = trim((string) ($row[34] ?? ''));
            if ($num !== '') {
                $workOrderNumbers[$num] = true;
            }
        }
        $workOrderNumbers = array_keys($workOrderNumbers);

        if (empty($workOrderNumbers)) {
            return 0;
        }


        $existingOrders = ProjectOrderPermit::whereIn('name', $workOrderNumbers)
            ->get()
            ->keyBy('name');

        $updates = [];

        foreach ($rows as $row) {
            $workOrderNumber = trim((string) ($row[34] ?? '')); // AI
            $typeCode        = trim((string) ($row[35] ?? '')); // AJ

            if ($workOrderNumber === '' || $typeCode === '') {
                continue;
            }

            if (!isset($existingOrders[$workOrderNumber])) {
                continue;
            }

            $order = $existingOrders[$workOrderNumber];
            $orderId = $order->id;

            $isContractor = isset($codeToOrderPermitId[$typeCode]);
            $isConsultant = isset($typeToOrderPermitId[$typeCode]);

            if (!$isContractor && !$isConsultant) {
                continue;
            }

            if (!isset($updates[$orderId])) {
                $updates[$orderId] = [];
            }

            if ($isContractor) {
                $updates[$orderId]['executing_entity']               = $this->value($row, 27);   // AB
                $updates[$orderId]['office']                        = $this->value($row, 37);   // AL
                $updates[$orderId]['contractor_basket']             = $this->value($row, 16);   // Q
                $updates[$orderId]['contractor_last_procedure_code'] = $this->value($row, 30);   // AE
                $updates[$orderId]['contractor_last_procedure_date'] = $this->parseDate($row, 28); // AC
                $updates[$orderId]['contractor_column_155_entry_date'] = $this->parseDate($row, 24); // Y
                $updates[$orderId]['material_balance_elec_contractor'] = $this->value($row, 13);   // N
                $updates[$orderId]['contractor_work_order_status']  = $this->value($row, 6);    // G
            } else {
                $updates[$orderId]['consultant_current_basket']     = $this->value($row, 16);   // Q
                $updates[$orderId]['consultant_assignment_date']    = $this->parseDate($row, 25); // Z
                $updates[$orderId]['consultant_last_procedure_code'] = $this->value($row, 30);   // AE
                $updates[$orderId]['consultant_last_procedure_date'] = $this->parseDate($row, 28); // AC
                $updates[$orderId]['consultant_column_155_entry_date'] = $this->parseDate($row, 24); // Y
                $updates[$orderId]['consultant_price']              = $this->parseFloat($row, 12); // M
            }
        }

        $updatedCount = 0;
        foreach ($updates as $orderId => $fields) {
            if (empty($fields)) {
                continue;
            }

            $fields['last_row_update_at'] = Carbon::now();

            ProjectOrderPermit::where('id', $orderId)->update($fields);
            $updatedCount++;
        }

        Log::info('Excel import completed', ['updated_orders' => $updatedCount, 'total_rows' => count($rows)]);
        return $updatedCount;
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
        if ($val === null) return null;

        return (float) $val;
    }
}

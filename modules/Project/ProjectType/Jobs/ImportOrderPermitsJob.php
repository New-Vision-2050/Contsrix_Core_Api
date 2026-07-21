<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Project\ProjectManagement\Models\ProjectContractor;
use Modules\Project\ProjectType\Models\OrderPermit;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;
use Illuminate\Support\Facades\Storage;

class ImportOrderPermitsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 1;

    private string $filePath;
    private string $progressKey;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->progressKey = 'import_order_permits_' . uniqid();
    }

    public function handle(): void
    {
        $fullPath = storage_path('app/public/' . $this->filePath);

        if (!file_exists($fullPath)) {
            Log::error('Import file not found: ' . $this->filePath);
            Cache::put($this->progressKey, ['status' => 'error', 'message' => 'File not found'], 600);
            return;
        }

        $rows = Excel::toArray([], $fullPath)[0] ?? [];

        if (empty($rows)) {
            Cache::put($this->progressKey, ['status' => 'finished', 'progress' => 100, 'updated' => 0], 600);
            Storage::disk('public')->delete($this->filePath);
            return;
        }

        $totalRows = count($rows);
        Cache::put($this->progressKey, [
            'status' => 'processing',
            'progress' => 0,
            'updated' => 0,
            'total' => $totalRows,
        ], 600);

        $workOrderNumbers = [];
        foreach ($rows as $row) {
            $num = trim((string)($row[34] ?? ''));
            if ($num !== '') $workOrderNumbers[$num] = true;
        }
        $workOrderNumbers = array_keys($workOrderNumbers);

        $existingOrders = ProjectOrderPermit::whereIn('name', $workOrderNumbers)
            ->with(['contractor', 'orderPermit.orderPermitType'])
            ->get()
            ->keyBy('name');

        $contractorUpdates = []; // [contractor_id => name]
        $updates = [];
        $logMessages = [];

        foreach ($rows as $index => $row) {
            $workOrderNumber = trim((string)($row[34] ?? ''));
            $typeCode = trim((string)($row[35] ?? ''));
            if ($workOrderNumber === '' || $typeCode === '') continue;

            if (!isset($existingOrders[$workOrderNumber])) continue;

            $order = $existingOrders[$workOrderNumber];
            $orderId = $order->id;

            $akValue = $this->value($row, 36);
            if ($akValue !== null) {
                $contractor = $order->contractor;
                if ($contractor) {
                    $currentName = $contractor->name;
                    if (empty($currentName)) {
                        $contractorUpdates[$contractor->id] = $akValue;
                    } elseif ($currentName !== $akValue) {
                        $msg = "Contractor name mismatch: DB='{$currentName}', Excel='{$akValue}'";
                        Log::warning("Order {$workOrderNumber}: {$msg}");
                        $logMessages[$orderId][] = $msg;
                    }
                } else {
                    $msg = "No contractor linked, cannot set name from Excel.";
                    Log::warning("Order {$workOrderNumber}: {$msg}");
                    $logMessages[$orderId][] = $msg;
                }
            }

            $orderPermit = $order->orderPermit;
            if (!$orderPermit) continue;

            $isContractor = $orderPermit->code !== null && (string)$orderPermit->code === $typeCode;
            $isConsultant = $orderPermit->type !== null && (string)$orderPermit->type === $typeCode;
            if (!$isContractor && !$isConsultant) continue;

            if (!isset($updates[$orderId])) $updates[$orderId] = [];

            if ($isContractor) {
                $updates[$orderId]['executing_entity'] = $this->value($row, 27);
                $updates[$orderId]['office'] = $this->value($row, 37);
                $updates[$orderId]['contractor_basket'] = $this->value($row, 16);
                $updates[$orderId]['contractor_last_procedure_code'] = $this->value($row, 30);
                $updates[$orderId]['contractor_last_procedure_date'] = $this->parseDate($row, 28);
                $updates[$orderId]['contractor_column_155_entry_date'] = $this->parseDate($row, 24);
                $updates[$orderId]['material_balance_elec_contractor'] = $this->value($row, 13);
                $updates[$orderId]['contractor_work_order_status'] = $this->value($row, 6);
            } else {
                $updates[$orderId]['consultant_current_basket'] = $this->value($row, 16);
                $updates[$orderId]['consultant_assignment_date'] = $this->parseDate($row, 25);
                $updates[$orderId]['consultant_last_procedure_code'] = $this->value($row, 30);
                $updates[$orderId]['consultant_last_procedure_date'] = $this->parseDate($row, 28);
                $updates[$orderId]['consultant_column_155_entry_date'] = $this->parseDate($row, 24);
                $updates[$orderId]['consultant_price'] = $this->parseFloat($row, 12);
            }

            if ($index % 200 === 0) {
                $progress = round(($index / $totalRows) * 100);
                Cache::put($this->progressKey, [
                    'status' => 'processing',
                    'progress' => $progress,
                    'updated' => 0,
                    'total' => $totalRows,
                ], 600);
            }
        }

        foreach ($contractorUpdates as $contractorId => $newName) {
            ProjectContractor::where('id', $contractorId)->update(['name' => $newName]);
            Log::info("Updated contractor name for ID {$contractorId} to '{$newName}'");
        }

        $updatedCount = 0;
        foreach ($updates as $orderId => $fields) {
            if (empty($fields)) continue;
            $fields['last_row_update_at'] = Carbon::now();

            if (isset($logMessages[$orderId])) {
                $newLog = implode("\n", $logMessages[$orderId]);
                $existingLog = ProjectOrderPermit::where('id', $orderId)->value('import_log');
                $fields['import_log'] = $existingLog
                    ? $existingLog . "\n" . $newLog
                    : $newLog;
            }

            ProjectOrderPermit::where('id', $orderId)->update($fields);
            $updatedCount++;
        }

        Log::info('Excel import completed via Job', ['updated' => $updatedCount, 'total_rows' => $totalRows]);

        Cache::put($this->progressKey, [
            'status' => 'finished',
            'progress' => 100,
            'updated' => $updatedCount,
            'total' => $totalRows,
        ], 600);

        Storage::disk('public')->delete($this->filePath);
    }

    public function getProgressKey(): string
    {
        return $this->progressKey;
    }

    private function value(array $row, int $index): ?string
    {
        $val = trim((string)($row[$index] ?? ''));
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

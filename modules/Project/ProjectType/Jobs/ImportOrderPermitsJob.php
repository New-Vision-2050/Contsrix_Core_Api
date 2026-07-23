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
use Modules\Project\ProjectType\Models\ProjectOrderPermit;
use Modules\Project\ProjectType\Models\UdsProjectOrderPermit;
use Modules\Project\ProjectType\Imports\UdsProjectOrderImport;
use Illuminate\Support\Facades\Storage;

class ImportOrderPermitsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 1;

    private string $filePath;
    private string $projectId;
    private string $companyId;
    private string $progressKey;

    public function __construct(string $filePath, string $projectId, string $companyId)
    {
        $this->filePath = $filePath;
        $this->projectId = $projectId;
        $this->companyId = $companyId;
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

        UdsProjectOrderPermit::where('project_id', $this->projectId)->delete();

        $import = new UdsProjectOrderImport($this->projectId, $this->companyId);
        Excel::import($import, $fullPath);

        $this->updateWorkOrdersFromUds();

        Storage::disk('public')->delete($this->filePath);

        Cache::put($this->progressKey, [
            'status' => 'finished',
            'progress' => 100,
            'updated' => true,
        ], 600);
    }

    private function updateWorkOrdersFromUds(): void
    {
        $allRecords = UdsProjectOrderPermit::where('project_id', $this->projectId)->get();
        $workOrderNumbers = $allRecords->pluck('name')->unique()->toArray();

        $existingOrders = ProjectOrderPermit::whereIn('name', $workOrderNumbers)
            ->with(['contractor', 'orderPermit', 'department'])
            ->get()
            ->keyBy('name');

        $updates = [];
        $logMessages = [];

        foreach ($allRecords as $record) {
            $workOrderNumber = $record->name;
            $typeCode = $record->type_code;

            if (!isset($existingOrders[$workOrderNumber])) continue;

            $order = $existingOrders[$workOrderNumber];
            $orderId = $order->id;

            $orderPermit = $order->orderPermit;
            if (!$orderPermit) continue;

            $isContractor = $orderPermit->code !== null && (string)$orderPermit->code === $typeCode;
            $isConsultant = $orderPermit->type !== null && (string)$orderPermit->type === $typeCode;
            if (!$isContractor && !$isConsultant) continue;

            if (!isset($updates[$orderId])) $updates[$orderId] = [];

            // معالجة المقاول (فقط للمقاول)
            if ($isContractor) {
                $akValue = $record->contractor_name;
                $currentContractor = $order->contractor;

                if ($akValue !== null) {
                    if ($currentContractor) {
                        $currentName = $currentContractor->name;
                        if ($currentName !== $akValue) {
                            $newContractor = ProjectContractor::where('name', $akValue)->first();
                            if ($newContractor) {
                                $updates[$orderId]['contractor_id'] = $newContractor->id;
                                $updates[$orderId]['import_log'] = null;
                            } else {
                                $msg = "[".Carbon::now()->toDateTimeString()."] Contractor name '{$akValue}' not found; kept '{$currentName}'.";
                                Log::warning("Order {$workOrderNumber}: {$msg}");
                                $logMessages[$orderId][] = $msg;
                            }
                        }
                    } else {
                        $newContractor = ProjectContractor::where('name', $akValue)->first();
                        if ($newContractor) {
                            $updates[$orderId]['contractor_id'] = $newContractor->id;
                            $updates[$orderId]['import_log'] = null;
                        } else {
                            $msg = "[".Carbon::now()->toDateTimeString()."] No contractor with name '{$akValue}' found.";
                            Log::warning("Order {$workOrderNumber}: {$msg}");
                            $logMessages[$orderId][] = $msg;
                        }
                    }
                }
            }

            if ($isContractor) {
                $updates[$orderId]['executing_entity'] = $record->executing_entity;
                $updates[$orderId]['office'] = $record->office;
                $updates[$orderId]['contractor_basket'] = $record->contractor_basket;
                $updates[$orderId]['contractor_last_procedure_code'] = $record->contractor_last_procedure_code;
                $updates[$orderId]['contractor_last_procedure_date'] = $record->contractor_last_procedure_date;
                $updates[$orderId]['contractor_column_155_entry_date'] = $record->contractor_column_155_entry_date;
                $updates[$orderId]['material_balance_elec_contractor'] = $record->material_balance_elec_contractor;
                $updates[$orderId]['contractor_work_order_status'] = $record->contractor_work_order_status;
            } else {
                $updates[$orderId]['consultant_current_basket'] = $record->consultant_current_basket;
                $updates[$orderId]['assigned_date'] = $record->assigned_date;
                $updates[$orderId]['consultant_assignment_date'] = $record->consultant_assignment_date;
                $updates[$orderId]['consultant_last_procedure_code'] = $record->consultant_last_procedure_code;
                $updates[$orderId]['consultant_last_procedure_date'] = $record->consultant_last_procedure_date;
                $updates[$orderId]['consultant_column_155_entry_date'] = $record->consultant_column_155_entry_date;
                $updates[$orderId]['price'] = $record->price;
                $updates[$orderId]['consultant_price'] = $record->consultant_price;
            }
        }

        $updatedCount = 0;
        foreach ($updates as $orderId => $fields) {
            if (empty($fields)) continue;
            $fields['last_row_update_at'] = Carbon::now();

            if (isset($logMessages[$orderId]) && !array_key_exists('import_log', $fields)) {
                $fields['import_log'] = implode("\n", $logMessages[$orderId]);
            }

            ProjectOrderPermit::where('id', $orderId)->update($fields);
            $updatedCount++;
        }

        Log::info('Excel import completed via Job', ['updated' => $updatedCount]);
    }
}

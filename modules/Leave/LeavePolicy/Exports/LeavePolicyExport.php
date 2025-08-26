<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Modules\Leave\LeavePolicy\Services\LeavePolicyCRUDService;
use Modules\Leave\LeavePolicy\Models\LeavePolicy;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeavePolicyExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private LeavePolicyCRUDService $leavePolicyService;
    private array $filters;

    public function __construct(LeavePolicyCRUDService $leavePolicyService, array $filters = [])
    {
        $this->leavePolicyService = $leavePolicyService;
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->leavePolicyService->getForExport($this->filters);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Total Days',
            'Day Type',
            'Rollover Allowed',
            'Max Days Per Request',
            'Upgrade Condition',
            'Allow Half Day',
            'Company ID',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * @param LeavePolicy $leavePolicy
     * @return array
     */
    public function map($leavePolicy): array
    {
        return [
            $leavePolicy->id,
            $leavePolicy->name,
            $leavePolicy->total_days ?: '-',
            $leavePolicy->day_type ?: '-',
            $leavePolicy->is_rollover_allowed ? 'Yes' : 'No',
            $leavePolicy->max_days_per_request ?: '-',
            $leavePolicy->upgrade_condition ?: '-',
            $leavePolicy->is_allow_half_day ? 'Yes' : 'No',
            $leavePolicy->company_id,
            $leavePolicy->created_at->format('Y-m-d H:i:s'),
            $leavePolicy->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Set RTL direction for the entire sheet to better handle Arabic text
        $sheet->setRightToLeft(true);

        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => 'center'],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0']
                ]
            ]
        ];
    }
}

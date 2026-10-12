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
            'Leave Type',
            'Has Balance',
            'Max Balance Days',
            'Total Days',
            'Min Notice Days',
            'Day Calculation Type',
            'Entitlement Type',
            'Entitlement Start From',
            'Promotion Type',
            'Promotion Duration (Months)',
            'Rollover Allowed',
            'Rollover Duration (Months)',
            'Allow Exceed Balance',
            'Max Days',
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
            $leavePolicy->leaveType->name ?? '-',
            $leavePolicy->leaveType?->is_deduct_from_balance ? 'Yes' : 'No',
            $leavePolicy->max_balance_days ?? '-',
            $leavePolicy->total_days ?? '-',
            $leavePolicy->min_notice_days ?? '-',
            $leavePolicy->day_calculation_type ?? '-',
            $leavePolicy->entitlement_type ?? '-',
            $leavePolicy->entitlement_start_from ?? '-',
            $leavePolicy->promotion_type ?? '-',
            $leavePolicy->promotion_duration_months ?? '-',
            $leavePolicy->is_rollover_allowed ? 'Yes' : 'No',
            $leavePolicy->rollover_duration_months ?? '-',
            $leavePolicy->allow_exceed_balance ? 'Yes' : 'No',
            $leavePolicy->max_days ?? '-',
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

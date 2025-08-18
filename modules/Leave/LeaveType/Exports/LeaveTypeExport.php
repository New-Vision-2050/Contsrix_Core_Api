<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveType\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Modules\Leave\LeaveType\Services\LeaveTypeCRUDService;
use Modules\Leave\LeaveType\Models\LeaveType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeaveTypeExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private LeaveTypeCRUDService $leaveTypeService;
    private array $filters;

    public function __construct(LeaveTypeCRUDService $leaveTypeService, array $filters = [])
    {
        $this->leaveTypeService = $leaveTypeService;
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->leaveTypeService->getForExport($this->filters);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Is Paid',
            'Deduct From Balance',
            'Company ID',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * @param LeaveType $leaveType
     * @return array
     */
    public function map($leaveType): array
    {
        return [
            $leaveType->id,
            $leaveType->name,
            $leaveType->is_payed ? 'Yes' : 'No',
            $leaveType->is_deduct_from_balance ? 'Yes' : 'No',
            $leaveType->company_id,
            $leaveType->created_at->format('Y-m-d H:i:s'),
            $leaveType->updated_at->format('Y-m-d H:i:s'),
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

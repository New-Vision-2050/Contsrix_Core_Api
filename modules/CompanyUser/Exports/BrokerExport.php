<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Modules\CompanyUser\Services\Broker\BrokerCRUDService;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BrokerExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private BrokerCRUDService $brokerService;
    private array $filters;

    public function __construct(BrokerCRUDService $brokerService, array $filters = [])
    {
        $this->brokerService = $brokerService;
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->brokerService->getForExport($this->filters);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Email',
            'Phone',
            'Status',
            'Role',
            'Company',
            'Branch',
            'Registration Date',
            'Last Activity',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * @param $broker
     * @return array
     */
    public function map($broker): array
    {
        return [
            $broker->id,
            $broker->user ? $broker->user->name : '-',
            $broker->user ? $broker->user->email : '-',
            $broker->user ? $broker->user->phone : '-',
            $broker->status === 1 ? 'Active' : ($broker->status === -1 ? 'Suspended' : 'Inactive'),
            'Broker',
            $broker->company ? $broker->company->name : '-',
            $broker->branch ? $broker->branch->name : '-',
            $broker->created_at ? $broker->created_at->format('Y-m-d') : '-',
            $broker->updated_at ? $broker->updated_at->format('Y-m-d H:i:s') : '-',
            $broker->created_at ? $broker->created_at->format('Y-m-d H:i:s') : '-',
            $broker->updated_at ? $broker->updated_at->format('Y-m-d H:i:s') : '-',
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

<?php

declare(strict_types=1);

namespace Modules\SubEntity\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Modules\SubEntity\Services\SubEntityRecordsService;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\User\Models\User;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SubEntityRecordsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private SubEntityRecordsService $subEntityRecordsService;
    private array $filters;

    public function __construct(SubEntityRecordsService $subEntityRecordsService, array $filters = [])
    {
        $this->subEntityRecordsService = $subEntityRecordsService;
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->subEntityRecordsService->getForExport($this->filters);
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
            'Branch',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * @param mixed $record
     * @return array
     */
    public function map($record): array
    {
        // Handle both CompanyUser and User models
        if ($record instanceof CompanyUser) {
            return [
                $record->id,
                $record->name ?? 'N/A',
                $record->email ?? 'N/A',
                $record->phone ?? 'N/A',
                $this->getStatusText($record->status ?? 0),
                $this->getRoleText($record->role ?? 0),
                $record->branch->name ?? 'N/A',
                $record->created_at ? $record->created_at->format('Y-m-d H:i:s') : 'N/A',
                $record->updated_at ? $record->updated_at->format('Y-m-d H:i:s') : 'N/A',
            ];
        } elseif ($record instanceof User) {
            // Get company user data if available
            $companyUser = $record->companyUserCompanies->first();
            
            return [
                $record->id,
                $record->name ?? 'N/A',
                $record->email ?? 'N/A',
                $record->phone ?? 'N/A',
                $companyUser ? $this->getStatusText($companyUser->status ?? 0) : 'N/A',
                $companyUser ? $this->getRoleText($companyUser->role ?? 0) : 'N/A',
                $companyUser && $companyUser->branch ? $companyUser->branch->name : 'N/A',
                $record->created_at ? $record->created_at->format('Y-m-d H:i:s') : 'N/A',
                $record->updated_at ? $record->updated_at->format('Y-m-d H:i:s') : 'N/A',
            ];
        }

        // Fallback for other model types
        return [
            $record->id ?? 'N/A',
            $record->name ?? 'N/A',
            $record->email ?? 'N/A',
            $record->phone ?? 'N/A',
            isset($record->status) ? $this->getStatusText($record->status) : 'N/A',
            'N/A',
            'N/A',
            isset($record->created_at) ? $record->created_at->format('Y-m-d H:i:s') : 'N/A',
            isset($record->updated_at) ? $record->updated_at->format('Y-m-d H:i:s') : 'N/A',
        ];
    }

    /**
     * Get status text representation
     *
     * @param int $status
     * @return string
     */
    private function getStatusText(int $status): string
    {
        return match($status) {
            1 => 'Active',
            0 => 'Inactive',
            -1 => 'Suspended',
            default => 'Unknown'
        };
    }

    /**
     * Get role text representation
     *
     * @param int $role
     * @return string
     */
    private function getRoleText(int $role): string
    {
        return match($role) {
            1 => 'Client',
            2 => 'Employee', 
            3 => 'Broker',
            default => 'Unknown'
        };
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

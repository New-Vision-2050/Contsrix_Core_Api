<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Modules\CompanyUser\Services\Client\ClientCRUDService;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClientExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private ClientCRUDService $clientService;
    private array $filters;

    public function __construct(ClientCRUDService $clientService, array $filters = [])
    {
        $this->clientService = $clientService;
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->clientService->getForExport($this->filters);
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
     * @param $client
     * @return array
     */
    public function map($client): array
    {
        return [
            $client->id,
            $client->user ? $client->user->name : '-',
            $client->user ? $client->user->email : '-',
            $client->user ? $client->user->phone : '-',
            $client->status === 1 ? 'Active' : ($client->status === -1 ? 'Suspended' : 'Inactive'),
            'Client',
            $client->company ? $client->company->name : '-',
            $client->managementHierarchy ? $client->managementHierarchy->name : '-',
            $client->created_at ? $client->created_at->format('Y-m-d') : '-',
            $client->updated_at ? $client->updated_at->format('Y-m-d H:i:s') : '-',
            $client->created_at ? $client->created_at->format('Y-m-d H:i:s') : '-',
            $client->updated_at ? $client->updated_at->format('Y-m-d H:i:s') : '-',
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

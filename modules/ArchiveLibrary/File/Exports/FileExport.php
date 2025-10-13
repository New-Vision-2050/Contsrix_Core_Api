<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Modules\ArchiveLibrary\File\Services\FileCRUDService;
use Modules\ArchiveLibrary\File\Models\File;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FileExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private FileCRUDService $fileService;

    private array $filters;

    public function __construct(FileCRUDService $fileService, array $filters = [])
    {
        $this->fileService = $fileService;
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->fileService->getForExport($this->filters);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Reference Number',
            'Start Date',
            'End Date',
            'Access Type',
            'Status',
            'Folder',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * @param File $file
     * @return array
     */
    public function map($file): array
    {
        return [
            $file->id,
            $file->name,
            $file->reference_number ?? '-',
            $file->start_date ? $file->start_date->format('Y-m-d') : '-',
            $file->end_date ? $file->end_date->format('Y-m-d') : '-',
            $file->access_type ?? '-',
            $file->status === 1 ? 'Active' : 'Inactive',
            $file->folder?->name ?? 'Root',
            $file->created_at->format('Y-m-d H:i:s'),
            $file->updated_at->format('Y-m-d H:i:s'),
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

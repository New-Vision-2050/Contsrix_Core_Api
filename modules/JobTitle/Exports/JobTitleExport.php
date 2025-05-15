<?php

declare(strict_types=1);

namespace Modules\JobTitle\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Modules\JobTitle\Services\JobTitleCRUDService;
use Modules\JobTitle\Models\JobTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JobTitleExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{

    private JobTitleCRUDService $jobTitleService;

    private array $filters;


    public function __construct(JobTitleCRUDService $jobTitleService, array $filters = [])
    {
        $this->jobTitleService = $jobTitleService;
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->jobTitleService->getForExport($this->filters);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Description',
            'Type',
            'Status',
            'Job Type',
            'User Count',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * @param JobTitle $jobTitle
     * @return array
     */
    public function map( $jobTitle): array
    {
        return [
            $jobTitle->id,
            $jobTitle->name,
            $jobTitle->description,
            $jobTitle->type,
            $jobTitle->status === 1 ? 'Active' : 'Inactive',
            $jobTitle->jobType ? $jobTitle->jobType->name : '-',
            $jobTitle->userProfissional?->count() ==0 ?"0":$jobTitle->userProfissional?->count(),
            $jobTitle->created_at->format('Y-m-d H:i:s'),
            $jobTitle->updated_at->format('Y-m-d H:i:s'),
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

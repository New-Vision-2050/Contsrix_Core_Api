<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Modules\Shared\JobType\Services\JobTypeCRUDService;
use Modules\Shared\JobType\Models\JobType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JobTypeExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private JobTypeCRUDService $jobTypeService;

    private array $filters;

    public function __construct(JobTypeCRUDService $jobTypeService, array $filters = [])
    {
        $this->jobTypeService = $jobTypeService;
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->jobTypeService->getForExport($this->filters);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Status',
            'Job Titles',
            'User Count',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * @param JobType $jobType
     * @return array
     */
    public function map($jobType): array
    {
        return [
            $jobType->id,
            $jobType->name,
            $jobType->status === 1 ? 'Active' : 'Inactive',
            $jobType->jobTitles?->pluck('name')->implode(', ') ?: ' ',
            $jobType->userProfissional?->count() ==0 ?  '0':$jobType->userProfissional->count(),
            $jobType->created_at->format('Y-m-d H:i:s'),
            $jobType->updated_at->format('Y-m-d H:i:s'),
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

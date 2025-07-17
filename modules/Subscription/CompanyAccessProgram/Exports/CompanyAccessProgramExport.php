<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Modules\Subscription\CompanyAccessProgram\Services\CompanyAccessProgramCRUDService;
use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;

class CompanyAccessProgramExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private CompanyAccessProgramCRUDService $companyAccessProgramService;
    private array $filters;

    public function __construct(CompanyAccessProgramCRUDService $companyAccessProgramService, array $filters = [])
    {
        $this->companyAccessProgramService = $companyAccessProgramService;
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->companyAccessProgramService->getForExport($this->filters);
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
            'Company Field',
            'Status',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * @param CompanyAccessProgram $companyAccessProgram
     * @return array
     */
    public function map($companyAccessProgram): array
    {
        return [
            $companyAccessProgram->id,
            $companyAccessProgram->name,
            $companyAccessProgram->description,
            $companyAccessProgram->companyField ? $companyAccessProgram->companyField->name : '-',
            $companyAccessProgram->is_active ? 'Active' : 'Inactive',
            $companyAccessProgram->created_at->format('Y-m-d H:i:s'),
            $companyAccessProgram->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}

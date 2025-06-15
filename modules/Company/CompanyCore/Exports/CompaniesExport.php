<?php

namespace Modules\Company\CompanyCore\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Modules\Company\CompanyCore\Models\Company;
use Illuminate\Support\Collection;

class CompaniesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $companies;

    public function __construct($companies = null)
    {
        $this->companies = $companies;
    }

    public function collection()
    {
        return $this->companies ?? Company::with([
            'country',
            'companyType',
            'companyField',
            'companyRegistrationType',
            'generalManager',
            'mainBranch',
            'companyLegalData',
            'companyOfficialDocuments',
            'companyAddress',
            'branches',
            'companyFields'
        ])->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Username',
            'Email',
            'Phone',
            'Country',
            'Company Type',
            'Company Field',
            'Registration Type',
            'General Manager',
            'Status',
            'Data Completion',
            'Activation Date',
            'Serial Number',
            'Address',
            'Legal Registration Number',
            'Number of Branches'
        ];
    }

    public function map($company): array
    {
        return [
            $company->id,
            $company->name,
            $company->user_name,
            $company->email,
            $company->phone,
            $company->country?->name ?? '',
            $company->companyType?->name ?? '',
            $company->companyField?->name ?? '',
            $company->companyRegistrationType?->name ?? '',
            $company->generalManager?->name ?? '',
            $company->is_active ? 'Active' : 'Inactive',
            $company->complete_data ? 'Complete' : 'Incomplete',
            $company->date_activate,
            $company->serial_no,
            $company->companyAddress?->full_address ?? '',
            $company->companyLegalData?->first()?->registration_number ?? '',
            $company->branches?->count() ?? 0
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
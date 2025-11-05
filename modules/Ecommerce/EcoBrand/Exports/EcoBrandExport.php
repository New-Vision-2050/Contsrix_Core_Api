<?php

namespace Modules\Ecommerce\EcoBrand\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Modules\Ecommerce\EcoBrand\Models\EcoBrand;
use Illuminate\Support\Collection;

class EcoBrandExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $brands;

    public function __construct($brands = null)
    {
        $this->brands = $brands;
    }

    public function collection()
    {
        if ($this->brands) {
            return $this->brands;
        }
        
        // Use select to limit fields and reduce memory usage
        // Only select columns that exist in the database
        return EcoBrand::get();
    }

    public function headings(): array
    {
        return [
            'المعرف',
            'الاسم',
            'الوصف',
            'معرف الشركة',
            'تاريخ الإنشاء',
            'تاريخ التحديث'
        ];
    }

    public function map($brand): array
    {
        return [
            $brand->id,
            $brand->name,
            $brand->description,
            $brand->company_id,
            $brand->created_at?->format('Y-m-d H:i:s') ?? '',
            $brand->updated_at?->format('Y-m-d H:i:s') ?? ''
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
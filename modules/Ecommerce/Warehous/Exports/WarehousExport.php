<?php

namespace Modules\Ecommerce\Warehous\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Modules\Ecommerce\Warehous\Models\Warehous;
use Illuminate\Support\Collection;

class WarehousExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $warehouses;

    public function __construct($warehouses = null)
    {
        $this->warehouses = $warehouses;
    }

    public function collection()
    {
        if ($this->warehouses) {
            return $this->warehouses;
        }
        
        // Get warehouses with all necessary relationships
        return Warehous::with([
            'company', 
            'country', 
            'city',
            'products'
        ])->withCount('products')->get();
    }

    public function headings(): array
    {
        return [
            'المعرف',
            'اسم المستودع',
            'الشركة',
            'الدولة',
            'المدينة',
            'المنطقة',
            'الشارع',
            'خط العرض',
            'خط الطول',
            'عدد المنتجات',
            'مستودع افتراضي',
            'نشط',
            'تاريخ الإنشاء',
            'تاريخ التحديث'
        ];
    }

    public function map($warehouse): array
    {
        // Get company name
        $companyName = '';
        if ($warehouse->company) {
            $companyName = $warehouse->company->name ?? '';
        }

        // Get country name
        $countryName = '';
        if ($warehouse->country) {
            if (is_array($warehouse->country->name)) {
                $countryName = $warehouse->country->name['ar'] ?? $warehouse->country->name['en'] ?? '';
            } else {
                $names = json_decode($warehouse->country->name, true);
                if (is_array($names)) {
                    $countryName = $names['ar'] ?? $names['en'] ?? '';
                } else {
                    $countryName = $warehouse->country->name ?? '';
                }
            }
        }

        // Get city name
        $cityName = '';
        if ($warehouse->city) {
            if (is_array($warehouse->city->name)) {
                $cityName = $warehouse->city->name['ar'] ?? $warehouse->city->name['en'] ?? '';
            } else {
                $names = json_decode($warehouse->city->name, true);
                if (is_array($names)) {
                    $cityName = $names['ar'] ?? $names['en'] ?? '';
                } else {
                    $cityName = $warehouse->city->name ?? '';
                }
            }
        }

        // Get products count
        $productsCount = $warehouse->products_count ?? $warehouse->products->count() ?? 0;

        return [
            $warehouse->id,
            $warehouse->name ?? '',
            $companyName,
            $countryName,
            $cityName,
            $warehouse->district ?? '',
            $warehouse->street ?? '',
            $warehouse->latitude ?? '',
            $warehouse->longitude ?? '',
            $productsCount,
            $warehouse->is_default ? 'نعم' : 'لا',
            $warehouse->is_active ? 'نشط' : 'غير نشط',
            $warehouse->created_at?->format('Y-m-d H:i:s') ?? '',
            $warehouse->updated_at?->format('Y-m-d H:i:s') ?? ''
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Set RTL direction for the entire sheet to better handle Arabic text
        $sheet->setRightToLeft(true);

        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 11,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center'
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0F766E'] // Teal color for warehouses
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ]
        ];
    }
}

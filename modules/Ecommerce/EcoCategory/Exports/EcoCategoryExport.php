<?php

namespace Modules\Ecommerce\EcoCategory\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Modules\Ecommerce\EcoCategory\Models\EcoCategory;
use Illuminate\Support\Collection;

class EcoCategoryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $categories;

    public function __construct($categories = null)
    {
        $this->categories = $categories;
    }

    public function collection()
    {
        if ($this->categories) {
            return $this->categories;
        }
        
        // Get categories with parent relationship for better data
        return EcoCategory::with(['parent', 'children'])
            ->withCount(['products', 'children'])
            ->get();
    }

    public function headings(): array
    {
        return [
            'المعرف',
            'الاسم (عربي)',
            'الاسم (إنجليزي)',
            'الفئة الأب',
            'الأولوية',
            'الحالة',
            'عدد المنتجات',
            'عدد الفئات الفرعية',
            'تاريخ الإنشاء',
            'تاريخ التحديث'
        ];
    }

    public function map($category): array
    {
        // Get translated names
        $nameAr = '';
        $nameEn = '';
        
        if (is_array($category->name)) {
            $nameAr = $category->name['ar'] ?? '';
            $nameEn = $category->name['en'] ?? '';
        } else {
            // If name is stored as JSON string, decode it
            $names = json_decode($category->name, true);
            if (is_array($names)) {
                $nameAr = $names['ar'] ?? '';
                $nameEn = $names['en'] ?? '';
            } else {
                $nameAr = $category->name ?? '';
            }
        }

        // Get parent name if exists
        $parentName = '';
        if ($category->parent) {
            if (is_array($category->parent->name)) {
                $parentName = $category->parent->name['ar'] ?? $category->parent->name['en'] ?? '';
            } else {
                $parentNames = json_decode($category->parent->name, true);
                if (is_array($parentNames)) {
                    $parentName = $parentNames['ar'] ?? $parentNames['en'] ?? '';
                } else {
                    $parentName = $category->parent->name ?? '';
                }
            }
        }

        return [
            $category->id,
            $nameAr,
            $nameEn,
            $parentName,
            $category->priority ?? 0,
            $category->is_active ? 'نشط' : 'غير نشط',
            $category->products_count ?? 0,
            $category->children_count ?? 0,
            $category->created_at?->format('Y-m-d H:i:s') ?? '',
            $category->updated_at?->format('Y-m-d H:i:s') ?? ''
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
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center'
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'] // Indigo color
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

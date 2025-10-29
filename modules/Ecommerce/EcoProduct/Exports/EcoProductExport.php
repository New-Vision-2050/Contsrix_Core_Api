<?php

namespace Modules\Ecommerce\EcoProduct\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use Illuminate\Support\Collection;

class EcoProductExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $products;

    public function __construct($products = null)
    {
        $this->products = $products;
    }

    public function collection()
    {
        if ($this->products) {
            return $this->products;
        }
        
        // Get products with all necessary relationships
        return EcoProduct::with([
            'category', 
            'subCategory', 
            'brand', 
            'warehouse',
            'translations'
        ])->get();
    }

    public function headings(): array
    {
        return [
            'المعرف',
            'الاسم (عربي)',
            'الاسم (إنجليزي)',
            'رمز المنتج (SKU)',
            'السعر',
            'السعر النهائي',
            'المخزون',
            'الفئة',
            'الفئة الفرعية',
            'العلامة التجارية',
            'المستودع',
            'النوع',
            'الجنس',
            'نوع الخصم',
            'قيمة الخصم',
            'نسبة الضريبة',
            'الحد الأدنى للطلب',
            'مبلغ الشحن',
            'يتطلب شحن',
            'الشحن مشمول في السعر',
            'مشمول في العروض',
            'كمية غير محدودة',
            'خاضع للضريبة',
            'السعر يشمل الضريبة',
            'مرئي',
            'تاريخ الإنشاء',
            'تاريخ التحديث'
        ];
    }

    public function map($product): array
    {
        // Get translated names
        $nameAr = '';
        $nameEn = '';
        
        if ($product->translations && $product->translations->count() > 0) {
            foreach ($product->translations as $translation) {
                if ($translation->locale === 'ar') {
                    $nameAr = $translation->content;
                } elseif ($translation->locale === 'en') {
                    $nameEn = $translation->content;
                }
            }
        }

        // Get category name
        $categoryName = '';
        if ($product->category) {
            if (is_array($product->category->name)) {
                $categoryName = $product->category->name['ar'] ?? $product->category->name['en'] ?? '';
            } else {
                $names = json_decode($product->category->name, true);
                if (is_array($names)) {
                    $categoryName = $names['ar'] ?? $names['en'] ?? '';
                } else {
                    $categoryName = $product->category->name ?? '';
                }
            }
        }

        // Get subcategory name
        $subCategoryName = '';
        if ($product->subCategory) {
            if (is_array($product->subCategory->name)) {
                $subCategoryName = $product->subCategory->name['ar'] ?? $product->subCategory->name['en'] ?? '';
            } else {
                $names = json_decode($product->subCategory->name, true);
                if (is_array($names)) {
                    $subCategoryName = $names['ar'] ?? $names['en'] ?? '';
                } else {
                    $subCategoryName = $product->subCategory->name ?? '';
                }
            }
        }

        // Get brand name
        $brandName = '';
        if ($product->brand) {
            if (is_array($product->brand->name)) {
                $brandName = $product->brand->name['ar'] ?? $product->brand->name['en'] ?? '';
            } else {
                $names = json_decode($product->brand->name, true);
                if (is_array($names)) {
                    $brandName = $names['ar'] ?? $names['en'] ?? '';
                } else {
                    $brandName = $product->brand->name ?? '';
                }
            }
        }

        // Get warehouse name
        $warehouseName = '';
        if ($product->warehouse) {
            $warehouseName = $product->warehouse->name ?? '';
        }

        // Calculate final price
        $finalPrice = $product->price;
        if ($product->discount_value > 0) {
            if ($product->discount_type === 'percentage') {
                $finalPrice = $product->price - ($product->price * $product->discount_value / 100);
            } elseif ($product->discount_type === 'amount') {
                $finalPrice = $product->price - $product->discount_value;
            }
        }

        return [
            $product->id,
            $nameAr,
            $nameEn,
            $product->sku ?? '',
            number_format($product->price ?? 0, 2),
            number_format($finalPrice, 2),
            $product->stock ?? 0,
            $categoryName,
            $subCategoryName,
            $brandName,
            $warehouseName,
            $product->type ?? '',
            $product->gender ?? '',
            $product->discount_type ?? '',
            $product->discount_value ?? 0,
            $product->vat_percentage ?? 0,
            $product->min_order_quantity ?? 1,
            number_format($product->shipping_amount ?? 0, 2),
            $product->requires_shipping ? 'نعم' : 'لا',
            $product->shipping_included_in_price ? 'نعم' : 'لا',
            $product->product_included ? 'نعم' : 'لا',
            $product->unlimited_quantity ? 'نعم' : 'لا',
            $product->is_taxable ? 'نعم' : 'لا',
            $product->price_includes_vat ? 'نعم' : 'لا',
            $product->is_visible ? 'مرئي' : 'مخفي',
            $product->created_at?->format('Y-m-d H:i:s') ?? '',
            $product->updated_at?->format('Y-m-d H:i:s') ?? ''
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
                    'startColor' => ['rgb' => '059669'] // Green color for products
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

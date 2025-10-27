<?php

declare(strict_types=1);

namespace Modules\Ecommerce\DealDay\Exports;

use App\Exports\BaseExport;
use Modules\Ecommerce\DealDay\Services\DealDayCRUDService;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DealDayExport extends BaseExport
{
    protected DealDayCRUDService $dealDayService;
    protected array $filters;

    public function __construct(
        DealDayCRUDService $dealDayService,
        array $filters = []
    ) {
        $this->dealDayService = $dealDayService;
        $this->filters = $filters;
        parent::__construct($dealDayService);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->dealDayService->getForExport($this->filters);
    }

    public function headings(): array
    {
        return [
            'الرقم التعريفي',
            'اسم العرض',
            'اسم الشركة',
            'اسم المنتج',
            'رمز المنتج (SKU)',
            'نوع الخصم',
            'قيمة الخصم',
            'الحالة',
            'تاريخ الإنشاء',
            'تاريخ التحديث'
        ];
    }

    public function map($dealDay): array
    {
        // Format discount value with unit
        $discountValueFormatted = '';
        if ($dealDay->discount_value) {
            if ($dealDay->discount_type === 'percentage') {
                $discountValueFormatted = $dealDay->discount_value . '%';
            } else {
                $discountValueFormatted = number_format((float)$dealDay->discount_value, 2) . ' ريال';
            }
        }

        return [
            $dealDay->id,
            $dealDay->name ?? 'غير محدد',
            $dealDay->company?->name ?? 'غير محدد',
            $dealDay->product?->name ?? 'غير محدد',
            $dealDay->product?->sku ?? 'غير محدد',
            $dealDay->discount_type === 'percentage' ? 'نسبة مئوية' : 'مبلغ ثابت',
            $discountValueFormatted,
            $dealDay->is_active ? 'نشط' : 'غير نشط',
            $dealDay->created_at?->format('Y-m-d H:i:s'),
            $dealDay->updated_at?->format('Y-m-d H:i:s')
        ];
    }

    public function getFilterableColumns(): array
    {
        return [
            'search',
            'name',
            'company_id',
            'product_id',
            'discount_type',
            'is_active',
            'created_from',
            'created_to'
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Style the first row as bold
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25, // ID
            'B' => 30, // Name
            'C' => 25, // Company
            'D' => 30, // Product Name
            'E' => 20, // SKU
            'F' => 15, // Discount Type
            'G' => 15, // Discount Value
            'H' => 12, // Status
            'I' => 20, // Created At
            'J' => 20, // Updated At
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FeatureDeal\Exports;

use App\Exports\BaseExport;
use Modules\Ecommerce\FeatureDeal\Services\FeatureDealCRUDService;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FeatureDealExport extends BaseExport
{
    protected FeatureDealCRUDService $featureDealService;
    protected array $filters;

    public function __construct(
        FeatureDealCRUDService $featureDealService,
        array $filters = []
    ) {
        $this->featureDealService = $featureDealService;
        $this->filters = $filters;
        parent::__construct($featureDealService);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->featureDealService->getForExport($this->filters);
    }

    public function headings(): array
    {
        return [
            'الرقم التعريفي',
            'اسم العرض المميز',
            'اسم الشركة',
            'تاريخ البداية',
            'تاريخ النهاية',
            'نوع الخصم',
            'قيمة الخصم',
            'الحالة',
            'حالة العرض',
            'تاريخ الإنشاء',
            'تاريخ التحديث'
        ];
    }

    public function map($featureDeal): array
    {
        // Format discount value with unit
        $discountValueFormatted = '';
        if ($featureDeal->discount_value) {
            if ($featureDeal->discount_type === 'percentage') {
                $discountValueFormatted = $featureDeal->discount_value . '%';
            } else {
                $discountValueFormatted = number_format((float)$featureDeal->discount_value, 2) . ' ريال';
            }
        }

        return [
            $featureDeal->id,
            $featureDeal->name ?? 'غير محدد',
            $featureDeal->company?->name ?? 'غير محدد',
            $featureDeal->start_date?->format('Y-m-d'),
            $featureDeal->end_date?->format('Y-m-d'),
            $featureDeal->discount_type === 'percentage' ? 'نسبة مئوية' : 'مبلغ ثابت',
            $discountValueFormatted,
            $featureDeal->is_active ? 'نشط' : 'غير نشط',
            $featureDeal->status_text ?? 'غير محدد',
            $featureDeal->created_at?->format('Y-m-d H:i:s'),
            $featureDeal->updated_at?->format('Y-m-d H:i:s')
        ];
    }

    public function getFilterableColumns(): array
    {
        return [
            'search',
            'name',
            'company_id',
            'discount_type',
            'min_discount_value',
            'max_discount_value',
            'is_active',
            'active_only',
            'inactive_only',
            'current_only',
            'start_date_from',
            'start_date_to',
            'end_date_from',
            'end_date_to',
            'created_from',
            'created_to',
            'updated_from',
            'updated_to'
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
            'D' => 15, // Start Date
            'E' => 15, // End Date
            'F' => 15, // Discount Type
            'G' => 15, // Discount Value
            'H' => 12, // Active Status
            'I' => 15, // Current Status
            'J' => 20, // Created At
            'K' => 20, // Updated At
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FlashDeal\Exports;

use App\Exports\BaseExport;
use Modules\Ecommerce\FlashDeal\Services\FlashDealCRUDService;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FlashDealExport extends BaseExport
{
    protected FlashDealCRUDService $flashDealService;
    protected array $filters;

    public function __construct(
        FlashDealCRUDService $flashDealService,
        array $filters = []
    ) {
        $this->flashDealService = $flashDealService;
        $this->filters = $filters;
        parent::__construct($flashDealService);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->flashDealService->getForExport($this->filters);
    }

    public function headings(): array
    {
        return [
            'الرقم التعريفي',
            'اسم العرض البرق',
            'اسم الشركة',
            'تاريخ البداية',
            'تاريخ النهاية',
            'الحالة',
            'حالة العرض',
            'نشط حالياً',
            'قادم',
            'منتهي',
            'تاريخ الإنشاء',
            'تاريخ التحديث'
        ];
    }

    public function map($flashDeal): array
    {
        return [
            $flashDeal->id,
            $flashDeal->name ?? 'غير محدد',
            $flashDeal->company?->name ?? 'غير محدد',
            $flashDeal->start_date?->format('Y-m-d H:i:s'),
            $flashDeal->end_date?->format('Y-m-d H:i:s'),
            $flashDeal->is_active ? 'نشط' : 'غير نشط',
            $flashDeal->status_text ?? 'غير محدد',
            $flashDeal->isActive() ? 'نعم' : 'لا',
            $flashDeal->isUpcoming() ? 'نعم' : 'لا',
            $flashDeal->isExpired() ? 'نعم' : 'لا',
            $flashDeal->created_at?->format('Y-m-d H:i:s'),
            $flashDeal->updated_at?->format('Y-m-d H:i:s')
        ];
    }

    public function getFilterableColumns(): array
    {
        return [
            'search',
            'name',
            'company_id',
            'is_active',
            'active_only',
            'inactive_only',
            'currently_active_only',
            'upcoming_only',
            'expired_only',
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
            'D' => 20, // Start Date
            'E' => 20, // End Date
            'F' => 12, // Active Status
            'G' => 15, // Status Text
            'H' => 15, // Currently Active
            'I' => 12, // Upcoming
            'J' => 12, // Expired
            'K' => 20, // Created At
            'L' => 20, // Updated At
        ];
    }
}

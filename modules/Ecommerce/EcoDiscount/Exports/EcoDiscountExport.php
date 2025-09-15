<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoDiscount\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Ecommerce\EcoDiscount\Services\EcoDiscountCRUDService;
use Illuminate\Support\Collection;

class EcoDiscountExport implements FromCollection, WithHeadings, WithMapping
{
    private EcoDiscountCRUDService $service;
    private array $filters;

    public function __construct(EcoDiscountCRUDService $service, array $filters = [])
    {
        $this->service = $service;
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        // Get all discounts for export (without pagination)
        $data = $this->service->list(1, 1000); // Large number to get all
        return collect($data['data']);
    }

    public function headings(): array
    {
        return [
            'ID',
            'الاسم',
            'الوصف',
            'الكود',
            'النوع',
            'القيمة',
            'الحد الأدنى للطلب',
            'الحد الأقصى للخصم',
            'حد الاستخدام',
            'عدد مرات الاستخدام',
            'تاريخ البداية',
            'تاريخ النهاية',
            'نشط',
            'ينطبق على',
            'عدد المنتجات',
            'تاريخ الإنشاء',
        ];
    }

    public function map($discount): array
    {
        return [
            $discount->id,
            $discount->name,
            $discount->description,
            $discount->code,
            $this->getTypeLabel($discount->type),
            $this->getValueLabel($discount->type, $discount->value),
            $discount->min_order_amount ? number_format($discount->min_order_amount, 2) . ' ريال' : '-',
            $discount->max_discount_amount ? number_format($discount->max_discount_amount, 2) . ' ريال' : '-',
            $discount->usage_limit ?? 'غير محدود',
            $discount->used_count,
            $discount->start_date?->format('Y-m-d H:i:s') ?? '-',
            $discount->end_date?->format('Y-m-d H:i:s') ?? '-',
            $discount->is_active ? 'نعم' : 'لا',
            $this->getAppliesToLabel($discount->applies_to),
            $discount->products->count(),
            $discount->created_at->format('Y-m-d H:i:s'),
        ];
    }

    private function getTypeLabel(string $type): string
    {
        return match($type) {
            'percentage' => 'نسبة مئوية',
            'fixed_amount' => 'مبلغ ثابت',
            'buy_x_get_y' => 'اشتري واحصل على',
            default => $type
        };
    }

    private function getValueLabel(string $type, float $value): string
    {
        return match($type) {
            'percentage' => $value . '%',
            'fixed_amount' => number_format($value, 2) . ' ريال',
            default => (string) $value
        };
    }

    private function getAppliesToLabel(string $appliesTo): string
    {
        return match($appliesTo) {
            'all_products' => 'جميع المنتجات',
            'specific_products' => 'منتجات محددة',
            'categories' => 'فئات محددة',
            default => $appliesTo
        };
    }
}

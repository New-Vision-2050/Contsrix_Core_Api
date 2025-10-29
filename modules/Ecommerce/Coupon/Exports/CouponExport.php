<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Coupon\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Modules\Ecommerce\Coupon\Models\Coupon;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class CouponExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $coupons;

    public function __construct($coupons = null)
    {
        $this->coupons = $coupons;
    }

    public function collection()
    {
        if ($this->coupons) {
            return $this->coupons;
        }
        
        // Get coupons with all necessary relationships
        return Coupon::with(['company', 'customer'])->get();
    }

    public function headings(): array
    {
        return [
            'المعرف',
            'العنوان',
            'الكود',
            'نوع الكوبون',
            'نوع الخصم',
            'قيمة الخصم',
            'الحد الأدنى للشراء',
            'الحد الأقصى للخصم',
            'الاستخدام الأقصى لكل مستخدم',
            'الشركة',
            'العميل المخصص',
            'تاريخ البداية',
            'تاريخ الانتهاء',
            'الحالة',
            'صالح حالياً',
            'نشط',
            'تاريخ الإنشاء',
            'تاريخ التحديث'
        ];
    }

    public function map($coupon): array
    {
        // Get company name
        $companyName = '';
        if ($coupon->company) {
            $companyName = $coupon->company->name ?? '';
        }

        // Get customer name/email
        $customerInfo = '';
        if ($coupon->customer) {
            $customerInfo = ($coupon->customer->name ?? '') . 
                           ($coupon->customer->email ? ' (' . $coupon->customer->email . ')' : '');
        }

        // Determine coupon type in Arabic
        $couponTypeArabic = '';
        switch ($coupon->coupon_type) {
            case 'discount_on_purchase':
                $couponTypeArabic = 'خصم على الشراء';
                break;
            case 'free_delivery':
                $couponTypeArabic = 'شحن مجاني';
                break;
            case 'first_order':
                $couponTypeArabic = 'الطلب الأول';
                break;
            default:
                $couponTypeArabic = $coupon->coupon_type ?? '';
        }

        // Determine discount type in Arabic
        $discountTypeArabic = '';
        switch ($coupon->discount_type) {
            case 'percentage':
                $discountTypeArabic = 'نسبة مئوية';
                break;
            case 'fixed':
                $discountTypeArabic = 'مبلغ ثابت';
                break;
            default:
                $discountTypeArabic = $coupon->discount_type ?? '';
        }

        // Format discount amount with unit
        $discountAmountFormatted = '';
        if ($coupon->discount_amount) {
            if ($coupon->discount_type === 'percentage') {
                $discountAmountFormatted = $coupon->discount_amount . '%';
            } else {
                $discountAmountFormatted = number_format((float)$coupon->discount_amount, 2) . ' ريال';
            }
        }

        // Determine current status
        $now = Carbon::now()->toDateString();
        $status = '';
        $isValid = '';

        if (!$coupon->is_active) {
            $status = 'غير نشط';
            $isValid = 'لا';
        } elseif ($coupon->expire_date < $now) {
            $status = 'منتهي الصلاحية';
            $isValid = 'لا';
        } elseif ($coupon->start_date > $now) {
            $status = 'قادم';
            $isValid = 'لا';
        } else {
            $status = 'جاري';
            $isValid = 'نعم';
        }

        return [
            $coupon->id,
            $coupon->title ?? '',
            $coupon->code ?? '',
            $couponTypeArabic,
            $discountTypeArabic,
            $discountAmountFormatted,
            $coupon->min_purchase ? number_format((float)$coupon->min_purchase, 2) . ' ريال' : '',
            $coupon->max_discount ? number_format((float)$coupon->max_discount, 2) . ' ريال' : 'غير محدود',
            $coupon->max_usage_per_user ?? 'غير محدود',
            $companyName,
            $customerInfo ?: 'عام',
            $coupon->start_date?->format('Y-m-d') ?? '',
            $coupon->expire_date?->format('Y-m-d') ?? '',
            $status,
            $isValid,
            $coupon->is_active ? 'نعم' : 'لا',
            $coupon->created_at?->format('Y-m-d H:i:s') ?? '',
            $coupon->updated_at?->format('Y-m-d H:i:s') ?? ''
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
                    'startColor' => ['rgb' => 'DC2626'] // Red color for coupons
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

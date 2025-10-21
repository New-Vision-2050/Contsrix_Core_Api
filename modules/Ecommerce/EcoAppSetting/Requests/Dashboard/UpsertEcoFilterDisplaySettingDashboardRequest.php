<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ecommerce\EcoAppSetting\DTO\Dashboard\UpsertEcoFilterDisplaySettingDashboardDTO;
use Ramsey\Uuid\Uuid;

class UpsertEcoFilterDisplaySettingDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'show_filter_in_app' => 'nullable|boolean',
            'show_category_filter' => 'nullable|boolean',
            'show_product_filter' => 'nullable|boolean',
            'show_color_filter' => 'nullable|boolean',
            'show_brand_filter' => 'nullable|boolean',
            'show_size_filter' => 'nullable|boolean',
            'show_price_filter' => 'nullable|boolean',
            'show_rating_filter' => 'nullable|boolean',
            'show_discount_filter' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'show_filter_in_app.boolean' => 'إظهار نظام الفلتر في التطبيق يجب أن يكون true أو false',
            'show_category_filter.boolean' => 'إظهار فلتر الفئات يجب أن يكون true أو false',
            'show_product_filter.boolean' => 'إظهار فلتر المنتجات يجب أن يكون true أو false',
            'show_color_filter.boolean' => 'إظهار فلتر الألوان يجب أن يكون true أو false',
            'show_brand_filter.boolean' => 'إظهار فلتر العلامات التجارية يجب أن يكون true أو false',
            'show_size_filter.boolean' => 'إظهار فلتر الأحجام يجب أن يكون true أو false',
            'show_price_filter.boolean' => 'إظهار فلتر الأسعار يجب أن يكون true أو false',
            'show_rating_filter.boolean' => 'إظهار فلتر التقييمات يجب أن يكون true أو false',
            'show_discount_filter.boolean' => 'إظهار فلتر التخفيضات يجب أن يكون true أو false',
        ];
    }

    public function createUpsertEcoFilterDisplaySettingDTO(): UpsertEcoFilterDisplaySettingDashboardDTO
    {
        return new UpsertEcoFilterDisplaySettingDashboardDTO(
            company_id: Uuid::fromString(tenant("id")),
            show_filter_in_app: (int) $this->get('show_filter_in_app', 1),
            show_category_filter: (int) $this->get('show_category_filter', 1),
            show_product_filter: (int) $this->get('show_product_filter', 1),
            show_color_filter: (int) $this->get('show_color_filter', 1),
            show_brand_filter: (int) $this->get('show_brand_filter', 1),
            show_size_filter: (int) $this->get('show_size_filter', 1),
            show_price_filter: (int) $this->get('show_price_filter', 1),
            show_rating_filter: (int) $this->get('show_rating_filter', 1),
            show_discount_filter: (int) $this->get('show_discount_filter', 1),
        );
    }
}

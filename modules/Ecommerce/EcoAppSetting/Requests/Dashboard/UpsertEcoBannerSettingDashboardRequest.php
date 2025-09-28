<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ecommerce\EcoAppSetting\DTO\Dashboard\UpsertEcoBannerSettingDashboardDTO;
use Modules\Ecommerce\EcoAppSetting\DTO\UpsertEcoBannerSettingDTO;
use Ramsey\Uuid\Uuid;

class UpsertEcoBannerSettingDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'banner_location' => 'nullable|string|in:top,middle,bottom',
            'banner_display_type' => 'nullable|string|in:grid,list,slider',
            'banner_count' => 'nullable|integer|min:1|max:8',
            'enable_banner' => 'nullable|boolean',
            'type_page' => 'nullable|string|in:home,category,product,shop,profile',
            'banners' => 'nullable|array',
            'banners.*' => 'file|mimes:jpg,jpeg,png,svg,webp'
        ];
    }

    public function messages(): array
    {
        return [
            'banner_location.in' => 'مكان ظهور البانر يجب أن يكون أعلى، وسط، أو أسفل',
            'banner_display_type.in' => 'نوع عرض البانر يجب أن يكون شبكة، قائمة، أو منزلق',
            'banner_count.integer' => 'عدد البانرات يجب أن يكون رقم صحيح',
            'banner_count.min' => 'عدد البانرات يجب أن يكون على الأقل 1',
            'banner_count.max' => 'عدد البانرات يجب أن يكون 8 كحد أقصى',
            'enable_banner.boolean' => 'ظهور البانرات في التطبيق يجب أن يكون true أو false',
            'type_page.in' => 'نوع الصفحة يجب أن يكون إحدى القيم التالية: الرئيسية، الفئات، المنتجات، المتجر، الملف الشخصي',
            'banners.array' => 'البانرات يجب أن تكون مصفوفة من الملفات',
            'banners.*.file' => 'كل عنصر في البانرات يجب أن يكون ملفًا',
            'banners.*.mimes' => 'كل ملف في البانرات يجب أن يكون من نوع jpg, jpeg, png, svg, أو webp',
        ];
    }

    public function createUpsertEcoBannerSettingDTO(): UpsertEcoBannerSettingDashboardDTO
    {
        return new UpsertEcoBannerSettingDashboardDTO(
            company_id: Uuid::fromString(tenant("id")),
            banner_location: $this->get('banner_location', 'top'),
            banner_display_type: $this->get('banner_display_type', 'slider'),
            banner_count: (int) $this->get('banner_count', 1),
            enable_banner: (int) $this->get('enable_banner', 1),
            type_page: $this->get('type_page'),
            banners: $this->hasFile('banners') ? $this->file('banners') : [],
        );
    }
}

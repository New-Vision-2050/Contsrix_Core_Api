<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ecommerce\EcoAppSetting\DTO\Dashboard\UpsertEcoCartSettingDashboardDTO;
use Modules\Ecommerce\EcoAppSetting\DTO\UpsertEcoCartSettingDTO;
use Ramsey\Uuid\Uuid;

class UpsertEcoCartSettingDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'show_cart_products' => 'nullable|boolean',
            'cart_display_type' => 'nullable|string|in:list,grid',
            'cart_columns_count' => 'nullable|integer|in:1,2,3',
            'show_cart_in_app' => 'nullable|boolean',
            'empty_cart_image' => 'nullable|file|mimes:jpg,jpeg,png,svg,webp|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'show_cart_products.boolean' => 'إظهار منتجات السلة يجب أن يكون true أو false',
            'cart_display_type.in' => 'نوع عرض السلة يجب أن يكون قائمة أو شبكة',
            'cart_columns_count.in' => 'عدد أعمدة السلة يجب أن يكون 1، 2، أو 3',
            'cart_columns_count.integer' => 'عدد أعمدة السلة يجب أن يكون رقم صحيح',
            'show_cart_in_app.boolean' => 'إظهار السلة في التطبيق يجب أن يكون true أو false',
            'empty_cart_image.file' => 'الصورة الافتراضية للعربة الفارغة يجب أن تكون ملف صورة',
            'empty_cart_image.mimes' => 'الصورة الافتراضية للعربة الفارغة يجب أن تكون من نوع jpg, jpeg, png, svg, أو webp',
            'empty_cart_image.max' => 'حجم الصورة الافتراضية للعربة الفارغة يجب ألا يزيد عن 2 ميجابايت',
        ];
    }

    public function createUpsertEcoCartSettingDTO(): UpsertEcoCartSettingDashboardDTO
    {
        return new UpsertEcoCartSettingDashboardDTO(
            company_id: Uuid::fromString(tenant("id")),
            show_cart_products: (int) $this->get('show_cart_products', 0),
            cart_display_type: $this->get('cart_display_type', 'list'),
            cart_columns_count: (int) $this->get('cart_columns_count', 2),
            show_cart_in_app: (int) $this->get('show_cart_in_app', 1),
            empty_cart_image: $this->hasFile('empty_cart_image') ? $this->file('empty_cart_image') : null,
        );
    }
}

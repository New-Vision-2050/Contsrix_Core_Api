<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ecommerce\EcoAppSetting\DTO\Dashboard\UpsertEcoProductCardSettingDashboardDTO;
use Modules\Ecommerce\EcoAppSetting\DTO\UpsertEcoProductCardSettingDTO;
use Ramsey\Uuid\Uuid;

class UpsertEcoProductCardSettingDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'show_product_name' => 'nullable|boolean',
            'show_product_description_card' => 'nullable|boolean',
            'show_product_price_card' => 'nullable|boolean',
            'show_product_color' => 'nullable|boolean',
            'show_product_size_card' => 'nullable|boolean',
            'show_similar_products_card' => 'nullable|boolean',
            'product_card_display_type' => 'nullable|string|in:list,grid',
            'product_card_columns_count' => 'nullable|integer|in:1,2,3',
            'show_discount_code' => 'nullable|boolean',
            'show_payment_details' => 'nullable|boolean',
            'show_product_card_in_app' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'show_product_name.boolean' => 'إظهار اسم المنتج يجب أن يكون true أو false',
            'show_product_description_card.boolean' => 'إظهار وصف المنتج يجب أن يكون true أو false',
            'show_product_price_card.boolean' => 'إظهار سعر المنتج يجب أن يكون true أو false',
            'show_product_color.boolean' => 'إظهار لون المنتج يجب أن يكون true أو false',
            'show_product_size_card.boolean' => 'إظهار حجم المنتج يجب أن يكون true أو false',
            'show_similar_products_card.boolean' => 'إظهار المنتجات المشابهة يجب أن يكون true أو false',
            'product_card_display_type.in' => 'نوع عرض بطاقة المنتج يجب أن يكون قائمة أو شبكة',
            'product_card_columns_count.in' => 'عدد أعمدة بطاقة المنتج يجب أن يكون 1، 2، أو 3',
            'product_card_columns_count.integer' => 'عدد أعمدة بطاقة المنتج يجب أن يكون رقم صحيح',
            'show_discount_code.boolean' => 'إظهار كود الخصم يجب أن يكون true أو false',
            'show_payment_details.boolean' => 'إظهار تفاصيل الدفع يجب أن يكون true أو false',
            'show_product_card_in_app.boolean' => 'إظهار بطاقة المنتج في التطبيق يجب أن يكون true أو false',
        ];
    }

    public function createUpsertEcoProductCardSettingDTO(): UpsertEcoProductCardSettingDashboardDTO
    {
        return new UpsertEcoProductCardSettingDashboardDTO(
            company_id: Uuid::fromString(tenant("id")),
            show_product_name: (int) $this->get('show_product_name', 0),
            show_product_description_card: (int) $this->get('show_product_description_card', 1),
            show_product_price_card: (int) $this->get('show_product_price_card', 1),
            show_product_color: (int) $this->get('show_product_color', 1),
            show_product_size_card: (int) $this->get('show_product_size_card', 1),
            show_similar_products_card: (int) $this->get('show_similar_products_card', 1),
            product_card_display_type: $this->get('product_card_display_type', 'list'),
            product_card_columns_count: (int) $this->get('product_card_columns_count', 2),
            show_discount_code: (int) $this->get('show_discount_code', 1),
            show_payment_details: (int) $this->get('show_payment_details', 1),
            show_product_card_in_app: (int) $this->get('show_product_card_in_app', 1),
        );
    }
}

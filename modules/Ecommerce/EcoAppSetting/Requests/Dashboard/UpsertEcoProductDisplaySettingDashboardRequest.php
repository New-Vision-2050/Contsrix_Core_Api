<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ecommerce\EcoAppSetting\DTO\Dashboard\UpsertEcoProductDisplaySettingDashboardDTO;
use Ramsey\Uuid\Uuid;

class UpsertEcoProductDisplaySettingDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_display_category' => 'nullable|string|in:latest,best_seller,news,featured,popular',
            'product_display_type' => 'nullable|string|in:list,grid',
            'product_columns_count' => 'nullable|integer|in:1,2,3,4',
            'product_rows_count' => 'nullable|integer|in:4,8,16,32',
            'show_products_in_app' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'product_display_category.in' => 'فئة عرض المنتجات يجب أن تكون إحدى القيم التالية: الأحدث، الأكثر مبيعاً، الأخبار، المميزة، الشائعة',
            'product_display_type.in' => 'نوع عرض المنتجات يجب أن يكون قائمة أو شبكة',
            'product_columns_count.in' => 'عدد الأعمدة يجب أن يكون 1، 2، 3، أو 4',
            'product_columns_count.integer' => 'عدد الأعمدة يجب أن يكون رقم صحيح',
            'product_rows_count.in' => 'عدد المنتجات يجب أن يكون 4، 8، 16، أو 32',
            'product_rows_count.integer' => 'عدد المنتجات يجب أن يكون رقم صحيح',
            'show_products_in_app.boolean' => 'ظهور المنتجات في التطبيق يجب أن يكون true أو false',
        ];
    }

    public function createUpsertEcoProductDisplaySettingDTO(): UpsertEcoProductDisplaySettingDashboardDTO
    {
        return new UpsertEcoProductDisplaySettingDashboardDTO(
            company_id: Uuid::fromString(tenant("id")),
            product_display_category: $this->get('product_display_category', 'latest'),
            product_display_type: $this->get('product_display_type', 'list'),
            product_columns_count: (int) $this->get('product_columns_count', 2),
            product_rows_count: (int) $this->get('product_rows_count', 8),
            show_products_in_app: (int) $this->get('show_products_in_app', 1),
        );
    }
}

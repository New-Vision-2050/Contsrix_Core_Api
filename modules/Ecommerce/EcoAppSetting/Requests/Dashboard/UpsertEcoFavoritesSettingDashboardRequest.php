<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ecommerce\EcoAppSetting\DTO\Dashboard\UpsertEcoFavoritesSettingDashboardDTO;
use Modules\Ecommerce\EcoAppSetting\DTO\UpsertEcoFavoritesSettingDTO;
use Ramsey\Uuid\Uuid;

class UpsertEcoFavoritesSettingDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'show_favorites_search' => 'nullable|boolean',
            'show_favorites_delete' => 'nullable|boolean',
            'show_favorites_products' => 'nullable|boolean',
            'favorites_display_type' => 'nullable|string|in:list,grid',
            'show_favorites_in_app' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'show_favorites_search.boolean' => 'إظهار البحث في المفضلة يجب أن يكون true أو false',
            'show_favorites_delete.boolean' => 'إظهار الحذف في المفضلة يجب أن يكون true أو false',
            'show_favorites_products.boolean' => 'إظهار المنتجات المفضلة يجب أن يكون true أو false',
            'favorites_display_type.in' => 'نوع عرض المفضلة يجب أن يكون قائمة أو شبكة',
            'show_favorites_in_app.boolean' => 'إظهار المفضلة في التطبيق يجب أن يكون true أو false',
        ];
    }

    public function createUpsertEcoFavoritesSettingDTO(): UpsertEcoFavoritesSettingDashboardDTO
    {
        return new UpsertEcoFavoritesSettingDashboardDTO(
            company_id: Uuid::fromString(tenant("id")),
            show_favorites_search: (int) $this->get('show_favorites_search', 0),
            show_favorites_delete: (int) $this->get('show_favorites_delete', 0),
            show_favorites_products: (int) $this->get('show_favorites_products', 1),
            favorites_display_type: $this->get('favorites_display_type', 'list'),
            show_favorites_in_app: (int) $this->get('show_favorites_in_app', 1),
        );
    }
}

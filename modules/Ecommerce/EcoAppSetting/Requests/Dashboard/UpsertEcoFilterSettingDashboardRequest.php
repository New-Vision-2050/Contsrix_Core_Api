<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Ecommerce\EcoAppSetting\DTO\Dashboard\UpsertEcoFilterSettingDashboardDTO;
use Modules\Ecommerce\EcoAppSetting\DTO\UpsertEcoFilterSettingDTO;
use Ramsey\Uuid\Uuid;

class UpsertEcoFilterSettingDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'filters' => 'required|array|min:1',
            'filters.*.filter_name' => 'required|string|max:255',
            'filters.*.filter_key' => 'required|string|in:newest,featured,price_low_high,price_high_low',
            'show_filter_in_app' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'filters.required' => 'المرشحات مطلوبة',
            'filters.array' => 'المرشحات يجب أن تكون مصفوفة',
            'filters.min' => 'يجب إضافة مرشح واحد على الأقل',
            'filters.*.filter_name.required' => 'اسم المرشح مطلوب',
            'filters.*.filter_name.string' => 'اسم المرشح يجب أن يكون نص',
            'filters.*.filter_name.max' => 'اسم المرشح يجب ألا يزيد عن 255 حرف',
            'filters.*.filter_key.required' => 'مفتاح المرشح مطلوب',
            'filters.*.filter_key.in' => 'مفتاح المرشح يجب أن يكون إحدى القيم التالية: الجديد، المميز، الأعلى سعراً، الأقل سعراً',
            'filters.*.is_active.boolean' => 'حالة المرشح يجب أن تكون true أو false',
            'filters.*.sort_order.integer' => 'ترتيب المرشح يجب أن يكون رقم صحيح',
            'filters.*.sort_order.min' => 'ترتيب المرشح يجب أن يكون 0 أو أكثر',
            'show_filter_in_app.boolean' => 'إظهار المرشحات في التطبيق يجب أن يكون true أو false',
        ];
    }

    public function createUpsertEcoFilterSettingDTO(): UpsertEcoFilterSettingDashboardDTO
    {
        return new UpsertEcoFilterSettingDashboardDTO(
            company_id: Uuid::fromString(tenant("id")),
            filters: $this->get('filters', []),
            show_filter_in_app: (int) $this->get('show_filter_in_app', 1),
        );
    }
}

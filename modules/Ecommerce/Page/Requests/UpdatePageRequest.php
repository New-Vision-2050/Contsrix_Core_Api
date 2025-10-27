<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Page\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\Page\Commands\UpdatePageCommand;
use Modules\Ecommerce\Page\Handlers\UpdatePageHandler;

class UpdatePageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'description' => 'sometimes|array',
            'description.ar' => 'sometimes|string',
            'description.en' => 'sometimes|string',
            'type' => 'sometimes|string|in:terms_conditions,privacy_policy,refund_policy,return_policy,cancellation_policy,shipping_policy,about_us,company_reliability',
        ];
    }

    public function messages(): array
    {
        return [
            'description.array' => 'الوصف يجب أن يكون مصفوفة تحتوي على اللغات',
            'description.ar.string' => 'الوصف باللغة العربية يجب أن يكون نص',
            'description.en.string' => 'الوصف باللغة الإنجليزية يجب أن يكون نص',
            'type.string' => 'نوع الصفحة يجب أن يكون نص',
            'type.in' => 'نوع الصفحة يجب أن يكون أحد القيم المحددة',
        ];
    }

    public function createUpdatePageCommand(): UpdatePageCommand
    {
        return new UpdatePageCommand(
            id: Uuid::fromString($this->route('id')),
            description: $this->input('description'),
            type: $this->input('type'),
        );
    }
}

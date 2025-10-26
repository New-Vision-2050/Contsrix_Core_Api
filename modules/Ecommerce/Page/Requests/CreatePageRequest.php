<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Page\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\Page\DTO\CreatePageDTO;

class CreatePageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'description' => 'required|array',
            'description.ar' => 'required|string',
            'description.en' => 'required|string',
            'type' => 'required|string|in:terms_conditions,privacy_policy,refund_policy,return_policy,cancellation_policy,shipping_policy,about_us,company_reliability',
            'company_id' => 'required|uuid|exists:companies,id',
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'الوصف مطلوب',
            'description.array' => 'الوصف يجب أن يكون مصفوفة تحتوي على اللغات',
            'description.ar.required' => 'الوصف باللغة العربية مطلوب',
            'description.ar.string' => 'الوصف باللغة العربية يجب أن يكون نص',
            'description.en.required' => 'الوصف باللغة الإنجليزية مطلوب',
            'description.en.string' => 'الوصف باللغة الإنجليزية يجب أن يكون نص',
            'type.required' => 'نوع الصفحة مطلوب',
            'type.string' => 'نوع الصفحة يجب أن يكون نص',
            'type.in' => 'نوع الصفحة يجب أن يكون أحد القيم المحددة',
            'company_id.required' => 'معرف الشركة مطلوب',
            'company_id.uuid' => 'معرف الشركة غير صحيح',
            'company_id.exists' => 'الشركة المحددة غير موجودة',
        ];
    }

    public function createCreatePageDTO(): CreatePageDTO
    {
        return new CreatePageDTO(
            description: $this->input('description'),
            type: $this->input('type'),
            companyId: $this->input('company_id'),
        );
    }
}

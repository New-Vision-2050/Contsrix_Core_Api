<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBusinessActivity\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoBusinessActivity\DTO\Dashboard\CreateEcoBusinessActivityDashboardDTO;

class CreateEcoBusinessActivityDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_field_id' => 'required|uuid|exists:company_fields,id',
            'business_name' => 'nullable|string|max:255',
            'commercial_registration_number' => 'nullable|string|max:100',
            'identity_number' => 'nullable|string|max:50',
            'owner_name' => 'nullable|string|max:255',
            'national_identity_numbers' => 'nullable|string|max:500',
            'tax_certificate_number' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'business_name.string' => 'الاسم التجاري يجب أن يكون نص صحيح.',
            'business_name.max' => 'الاسم التجاري يجب ألا يتجاوز 255 حرف.',
            'commercial_registration_number.string' => 'رقم السجل التجاري يجب أن يكون نص صحيح.',
            'commercial_registration_number.max' => 'رقم السجل التجاري يجب ألا يتجاوز 100 حرف.',
            'identity_number.string' => 'رقم الهوية يجب أن يكون نص صحيح.',
            'identity_number.max' => 'رقم الهوية يجب ألا يتجاوز 50 حرف.',
            'owner_name.string' => 'اسم المالك يجب أن يكون نص صحيح.',
            'owner_name.max' => 'اسم المالك يجب ألا يتجاوز 255 حرف.',
            'national_identity_numbers.string' => 'أرقام الهوية الوطنية يجب أن تكون نص صحيح.',
            'national_identity_numbers.max' => 'أرقام الهوية الوطنية يجب ألا تتجاوز 500 حرف.',
            'tax_certificate_number.string' => 'رقم الشهادة الضريبية يجب أن يكون نص صحيح.',
            'tax_certificate_number.max' => 'رقم الشهادة الضريبية يجب ألا يتجاوز 100 حرف.',
        ];
    }

    public function createCreateEcoBusinessActivityDTO(): CreateEcoBusinessActivityDashboardDTO
    {
        return new CreateEcoBusinessActivityDashboardDTO(
            companyId: Uuid::fromString(tenant("id")),
            companyFieldId: Uuid::fromString($this->get('company_field_id')),
            businessName: $this->get('business_name'),
            commercialRegistrationNumber: $this->get('commercial_registration_number'),
            identityNumber: $this->get('identity_number'),
            ownerName: $this->get('owner_name'),
            nationalIdentityNumbers: $this->get('national_identity_numbers'),
            taxCertificateNumber: $this->get('tax_certificate_number'),
        );
    }
}

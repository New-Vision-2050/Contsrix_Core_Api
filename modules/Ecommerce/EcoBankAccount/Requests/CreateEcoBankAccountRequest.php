<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBankAccount\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoBankAccount\DTO\CreateEcoBankAccountDTO;

class CreateEcoBankAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'bank_id' => 'required|string|max:255',
            'account_holder_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50|unique:eco_bank_accounts,account_number,NULL,id,company_id,' . tenant('id'),
            'iban' => 'required|string|max:50',
            'country_id' => 'required|string|max:100',
            'is_primary' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'bank_id.required' => 'معرف البنك مطلوب.',
            'bank_id.string' => 'معرف البنك يجب أن يكون نص.',
            'account_holder_name.required' => 'اسم صاحب الحساب مطلوب.',
            'account_holder_name.string' => 'اسم صاحب الحساب يجب أن يكون نص.',
            'account_number.required' => 'رقم الحساب مطلوب.',
            'account_number.unique' => 'رقم الحساب موجود مسبقاً.',
            'iban.required' => 'رقم الآيبان مطلوب.',
            'iban.string' => 'رقم الآيبان يجب أن يكون نص.',
            'country_id.required' => 'معرف الدولة مطلوب.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->input('is_primary')) {
                // Check if there's already a primary account for this company
                $existingPrimary = \DB::table('eco_bank_accounts')
                    ->where('company_id', tenant('id'))
                    ->where('is_primary', true)
                    ->exists();
                
                if ($existingPrimary) {
                    $validator->errors()->add('is_primary', 'يوجد حساب أساسي بالفعل لهذه الشركة.');
                }
            }
        });
    }

    public function createCreateEcoBankAccountDTO(): CreateEcoBankAccountDTO
    {
        return new CreateEcoBankAccountDTO(
            companyId: Uuid::fromString(tenant('id')),
            bankId: $this->input('bank_id'),
            accountHolderName: $this->input('account_holder_name'),
            accountNumber: $this->input('account_number'),
            iban: $this->input('iban'),
            countryId: $this->input('country_id'),
            isPrimary: (bool) $this->input('is_primary', false),
            isActive: (bool) $this->input('is_active', true),
        );
    }
}

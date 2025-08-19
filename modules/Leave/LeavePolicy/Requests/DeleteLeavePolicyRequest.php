<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Leave\LeavePolicy\Rules\AnnualYearProtectionRules;

class DeleteLeavePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $id = Uuid::fromString($this->route('id'));
        
        if (!AnnualYearProtectionRules::canDelete($id)) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [
            'forbidden' => 'Cannot delete Annual Year policy. This policy is protected from deletion.',
        ];
    }
}

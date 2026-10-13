<?php

declare(strict_types=1);

namespace Modules\Advance\AdvancePolicy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Advance\AdvancePolicy\DTO\AdvancePolicyDTO;

class CreateAdvancePolicyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('advance_policies', 'name')->where('company_id', tenant('id')),
            ],
            'max_amount' => 'required|numeric|min:0.01|max:9999999999.99',
            'max_installments' => 'required|integer|min:1|max:120',
            'max_active_advances' => 'sometimes|integer|min:1|max:10',
            'requires_attachment' => 'sometimes|boolean',
        ];
    }

    public function createDTO(): AdvancePolicyDTO
    {
        return new AdvancePolicyDTO(
            name: (string) $this->get('name'),
            max_amount: (float) $this->get('max_amount'),
            max_installments: (int) $this->get('max_installments'),
            max_active_advances: (int) $this->get('max_active_advances', 1),
            requires_attachment: (bool) $this->get('requires_attachment', false),
        );
    }
}

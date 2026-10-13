<?php

declare(strict_types=1);

namespace Modules\Advance\AdvanceRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Advance\AdvanceRequest\DTO\CreateAdvanceRequestDTO;

class CreateAdvanceRequestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'advance_policy_id' => 'required|uuid|exists:advance_policies,id,company_id,' . tenant('id'),
            'amount' => 'required|numeric|min:0.01|max:9999999999.99',
            'installments' => 'required|integer|min:1|max:120',
            'reason' => 'required|string|max:1000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'string|max:1024',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function createDTO(): CreateAdvanceRequestDTO
    {
        return new CreateAdvanceRequestDTO(
            advance_policy_id: (string) $this->get('advance_policy_id'),
            amount: (float) $this->get('amount'),
            installments: (int) $this->get('installments'),
            reason: (string) $this->get('reason'),
            attachments: $this->get('attachments'),
            notes: $this->get('notes'),
        );
    }
}

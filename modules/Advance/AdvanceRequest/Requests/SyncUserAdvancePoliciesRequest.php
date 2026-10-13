<?php

declare(strict_types=1);

namespace Modules\Advance\AdvanceRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncUserAdvancePoliciesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'advance_policy_ids' => 'present|array',
            'advance_policy_ids.*' => 'uuid|exists:advance_policies,id,company_id,' . tenant('id'),
        ];
    }

    /**
     * @return list<string>
     */
    public function advancePolicyIds(): array
    {
        return array_values(array_map('strval', $this->get('advance_policy_ids', [])));
    }
}

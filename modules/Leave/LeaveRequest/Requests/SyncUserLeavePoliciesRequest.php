<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncUserLeavePoliciesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'leave_policy_ids' => 'present|array',
            'leave_policy_ids.*' => 'uuid|exists:leave_policies,id,company_id,' . tenant('id'),
        ];
    }

    /**
     * @return list<string>
     */
    public function leavePolicyIds(): array
    {
        return array_values(array_map('strval', $this->get('leave_policy_ids', [])));
    }
}

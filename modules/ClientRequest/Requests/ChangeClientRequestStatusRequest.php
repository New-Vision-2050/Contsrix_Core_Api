<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeClientRequestStatusRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'client_request_id' => $this->route('id'),
        ]);
    }

    public function rules(): array
    {
        return [
            'client_request_id'     => ['required', 'uuid', Rule::exists('client_requests', 'id')],
            'status_client_request' => ['required', 'string', 'in:pending,accepted,rejected,draft'],
            'reject_cause'          => ['nullable', 'string', 'required_if:status_client_request,rejected'],
        ];
    }
}

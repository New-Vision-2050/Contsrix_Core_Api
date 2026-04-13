<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeClientRequestStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status_client_request' => 'required|string|in:pending,accepted,rejected,draft',
            'reject_cause' => 'nullable|string|required_if:status_client_request,rejected',
        ];
    }
}

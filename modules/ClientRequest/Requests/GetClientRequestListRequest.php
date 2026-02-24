<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetClientRequestListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'client_request_type_id' => 'nullable|integer|exists:client_request_types,id',
            'client_request_receiver_from_id' => 'nullable|integer|exists:client_request_receiver_from,id',
            'client_type' => 'nullable|string',
            'client_id' => 'nullable|uuid',
            'status_client_request' => 'nullable|string|in:pending,rejected,accepted',
            'content' => 'nullable|string',
            'term_setting_id' => 'nullable|integer|exists:term_settings,id',
            'branch_id' => 'nullable|integer|exists:management_hierarchies,id',
            'management_id' => 'nullable|integer|exists:management_hierarchies,id',
            'service_id' => 'nullable|integer|exists:client_request_services,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'pending' => 'nullable|boolean',
            'accepted' => 'nullable|boolean',
            'rejected' => 'nullable|boolean',
        ];
    }
}

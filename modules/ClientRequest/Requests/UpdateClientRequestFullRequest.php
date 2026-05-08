<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\ClientRequest\DTO\UpdateClientRequestDTO;

class UpdateClientRequestFullRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'client_request_type_id' => 'sometimes|required|integer|exists:client_request_types,id',
            'client_request_receiver_from_id' => 'sometimes|required|integer|exists:client_request_receiver_from,id',
            'client_type' => 'sometimes|required|string|max:255',
            'client_id' => 'sometimes|required|uuid',
            'content' => 'nullable|string',
            'receiver_employee_ids' => 'nullable|array',
            'receiver_employee_ids.*' => 'uuid',
            'reject_cause' => 'nullable|string',
            'status_client_request' => 'nullable|string|in:pending,rejected,accepted,draft',
            'client_price_offer_status' => 'nullable|string|in:pending,rejected,accepted,draft',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'integer|exists:client_request_services,id',
            'term_setting_ids' => 'nullable|array',
            'term_setting_ids.*.term_service_id' => 'required|integer|exists:term_service_settings,id',
            'term_setting_ids.*.term_ids' => 'required|array|min:1',
            'term_setting_ids.*.term_ids.*' => 'integer|exists:term_settings,id',
            'branch_id' => 'nullable|integer|exists:management_hierarchies,id',
            'management_id' => 'nullable|integer|exists:management_hierarchies,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,svg,webp|max:10240',
        ];
    }

    public function createUpdateClientRequestDTO(): UpdateClientRequestDTO
    {
        return new UpdateClientRequestDTO(
            id: $this->route('id'),
            client_request_type_id: (int) $this->get('client_request_type_id'),
            client_request_receiver_from_id: (int) $this->get('client_request_receiver_from_id'),
            client_type: $this->get('client_type'),
            client_id: $this->get('client_id'),
            content: $this->get('content'),
            receiver_employee_ids: $this->get('receiver_employee_ids'),
            reject_cause: $this->get('reject_cause'),
            status_client_request: $this->get('status_client_request'),
            client_price_offer_status: $this->get('client_price_offer_status'),
            service_ids: $this->get('service_ids', []),
            term_setting_ids: $this->get('term_setting_ids', []),
            branch_id: $this->get('branch_id') ? (int) $this->get('branch_id') : null,
            management_id: $this->get('management_id') ? (int) $this->get('management_id') : null,
            attachments: $this->file('attachments', []),
        );
    }
}

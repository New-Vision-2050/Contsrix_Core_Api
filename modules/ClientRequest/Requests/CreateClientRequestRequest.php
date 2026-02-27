<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\ClientRequest\DTO\CreateClientRequestDTO;

class CreateClientRequestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'client_request_type_id' => 'required|integer|exists:client_request_types,id',
            'client_request_receiver_from_id' => 'required|integer|exists:client_request_receiver_from,id',
            'client_type' => 'required|string|max:255',
            'client_id' => 'required|uuid',
            'content' => 'nullable|string',
            'status_client_request' => 'nullable|string|in:pending,rejected,accepted',
            'client_price_offer_status' => 'nullable|string|in:pending,rejected,accepted',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'integer|exists:client_request_services,id',
            'term_setting_id' => 'nullable|integer|exists:term_settings,id',
            'branch_id' => 'nullable|integer|exists:management_hierarchies,id',
            'management_id' => 'nullable|integer|exists:management_hierarchies,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,svg,webp|max:10240',
        ];
    }

    public function createCreateClientRequestDTO(): CreateClientRequestDTO
    {
        return new CreateClientRequestDTO(
            client_request_type_id: (int) $this->get('client_request_type_id'),
            client_request_receiver_from_id: (int) $this->get('client_request_receiver_from_id'),
            client_type: $this->get('client_type'),
            client_id: $this->get('client_id'),
            content: $this->get('content'),
            status_client_request: $this->get('status_client_request', 'pending'),
            client_price_offer_status: $this->get('client_price_offer_status', 'pending'),
            service_ids: $this->get('service_ids', []),
            term_setting_id: $this->get('term_setting_id') ? (int) $this->get('term_setting_id') : null,
            branch_id: $this->get('branch_id') ? (int) $this->get('branch_id') : null,
            management_id: $this->get('management_id') ? (int) $this->get('management_id') : null,
            attachments: $this->file('attachments', []),
        );
    }
}

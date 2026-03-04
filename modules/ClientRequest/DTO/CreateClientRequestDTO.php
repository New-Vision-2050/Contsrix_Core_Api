<?php

declare(strict_types=1);

namespace Modules\ClientRequest\DTO;

use Illuminate\Http\UploadedFile;

class CreateClientRequestDTO
{
    public function __construct(
        public int $client_request_type_id,
        public int $client_request_receiver_from_id,
        public string $client_type,
        public string $client_id,
        public ?string $content = null,
        public ?string $receiver_phone = null,
        public ?string $receiver_email = null,
        public ?string $receiver_broker_type = null,
        public ?string $receiver_broker_id = null,
        public ?string $receiver_employee_id = null,
        public string $status_client_request = 'pending',
        public string $client_price_offer_status = 'pending',
        public array $service_ids = [],
        public array $term_setting_ids = [],
        public ?int $branch_id = null,
        public ?int $management_id = null,
        public array $attachments = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => tenant('id'),
            'client_request_type_id' => $this->client_request_type_id,
            'client_request_receiver_from_id' => $this->client_request_receiver_from_id,
            'client_type' => $this->client_type,
            'client_id' => $this->client_id,
            'content' => $this->content,
            'receiver_phone' => $this->receiver_phone,
            'receiver_email' => $this->receiver_email,
            'receiver_broker_type' => $this->receiver_broker_type,
            'receiver_broker_id' => $this->receiver_broker_id,
            'receiver_employee_id' => $this->receiver_employee_id,
            'status_client_request' => $this->status_client_request,
            'client_price_offer_status' => $this->client_price_offer_status,
            'branch_id' => $this->branch_id,
            'management_id' => $this->management_id,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\ClientRequest\DTO;

use Illuminate\Http\UploadedFile;

class UpdateClientRequestDTO
{
    public function __construct(
        public string $id,
        public ?int $client_request_type_id = null,
        public ?int $client_request_receiver_from_id = null,
        public ?string $client_type = null,
        public ?string $client_id = null,
        public ?string $content = null,
        public ?string $status_client_request = null,
        public ?string $client_price_offer_status = null,
        public array $service_ids = [],
        public array $term_setting_ids = [],
        public ?int $branch_id = null,
        public ?int $management_id = null,
        public array $attachments = [],
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'client_request_type_id' => $this->client_request_type_id,
            'client_request_receiver_from_id' => $this->client_request_receiver_from_id,
            'client_type' => $this->client_type,
            'client_id' => $this->client_id,
            'content' => $this->content,
            'status_client_request' => $this->status_client_request,
            'client_price_offer_status' => $this->client_price_offer_status,
            'branch_id' => $this->branch_id,
            'management_id' => $this->management_id,
        ], function ($value) {
            return $value !== null;
        });
    }
}

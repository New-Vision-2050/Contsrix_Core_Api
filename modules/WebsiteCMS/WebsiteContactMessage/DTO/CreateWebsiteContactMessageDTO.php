<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactMessage\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateWebsiteContactMessageDTO
{
    public function __construct(
        public string $name,
        public string $phone,
        public string $email,
        public ?string $address,
        public int $status,
        public string $message,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'status' => $this->status,
            'message' => $this->message,
            'company_id' => tenant('id'),
        ];
    }
}

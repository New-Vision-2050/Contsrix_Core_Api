<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoClient\DTO;

use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\UuidInterface;

class CreateEcoClientDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public string $name,
        public string $email,
        public string $password,
        public ?string $phoneCode = null,
        public ?string $phone = null,
        public ?UploadedFile $profileImage = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'phone_code' => $this->phoneCode,
            'phone' => $this->phone,
            'profile_image' => $this->profileImage
        ];
    }
}

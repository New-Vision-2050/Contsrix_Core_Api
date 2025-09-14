<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoClient\Commands;

use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\UuidInterface;

class UpdateEcoClientCommand
{
    public function __construct(
        private UuidInterface $id,
        private ?string $name = null,
        private ?string $email = null,
        private ?string $password = null,
        private ?string $phoneCode = null,
        private ?string $phone = null,
        private ?UploadedFile $profileImage = null
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
    public function getprofileImage(): ?UploadedFile
    {
            return $this->profileImage;
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'phone_code' => $this->phoneCode,
            'phone' => $this->phone,
            'profile_image' => $this->profileImage,
        ], fn ($value) => !is_null($value));
    }
}

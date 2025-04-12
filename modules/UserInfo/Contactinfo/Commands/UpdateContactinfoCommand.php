<?php

declare(strict_types=1);

namespace Modules\UserInfo\Contactinfo\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateContactinfoCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $email,
        private string $other_phone,
        private string $phone,
        private string $phone_code,
        private string $landline_number,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }


    public function toArray(): array
    {
        return array_filter([
            'email'=> $this->email,
            'other_phone'=> $this->other_phone,
            'phone'=> $this->phone,
            'phone_code'=> $this->phone_code,
            'landline_number'=> $this->landline_number,
        ]);
    }
}

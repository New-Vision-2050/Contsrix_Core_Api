<?php

declare(strict_types=1);

namespace Modules\UserInfo\Contactinfo\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateContactinfoCommand
{
    public function __construct(
        public string $company_id,
        public string $global_id,
        public ?string $email,
        public ?string $other_phone,
        public ?string $code_other_phone,
        public ?string $phone,
        public ?string $phone_code,
        public ?string $landline_number,
    ) {
    }



    public function toArray(): array
    {
        return array_filter([
            'company_id' => $this->company_id,
            'global_id' => $this->global_id,
            'email'=> $this->email,
            'other_phone'=> $this->other_phone,
            'code_other_phone' => $this->code_other_phone,
            'phone'=> $this->phone,
            'phone_code'=> $this->phone_code,
            'landline_number'=> $this->landline_number,
        ]);
    }
}

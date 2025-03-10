<?php

declare(strict_types=1);

namespace Modules\CompanyUser\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateCompanyUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public ? string $country_id,
        public string $phone,
        public string $job_title_id,
        public ? string $border_number ,
        public ? string $residence,
        public ? string $identity,
        public ? string $passport,

    ) {
    }

    public function getCoutryId()
    {
        return $this->country_id;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'country_id' => $this->country_id,
            'job_title_id'=>$this->job_title_id,
            'phone' => $this->phone,
            'border_number' => $this->border_number,
            'residence' => $this->residence,
            "identity"=>$this->identity,
            "passport"=>$this->passport,
        ];
    }

}

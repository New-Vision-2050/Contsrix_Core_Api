<?php

declare(strict_types=1);

namespace Modules\CompanyUser\DTO;


class CreateCompanyUserDTO
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public ? string $country_id,
        public string $phone,
        public string $job_title_id,
        public ? string $border_number ,
        public ? string $residence,
        public ? string $identity,
        public ? string $passport,
        public ? string $time_zone_id,
        public ? string $language_id,
        public ? string $currency_id,
    ) {
    }

    public function getCoutryId()
    {
        return $this->country_id;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->firstName.' '.$this->lastName,
            'email' => $this->email,
            'country_id' => $this->country_id,
            'job_title_id'=>$this->job_title_id,
            'phone' => $this->phone,
            'border_number' => $this->border_number,
            'residence' => $this->residence,
            "identity"=>$this->identity,
            "passport"=>$this->passport,
            'time_zone_id' => $this->time_zone_id,
            'language_id' => $this->language_id,
            'currency_id' => $this->currency_id,
        ];
    }

}

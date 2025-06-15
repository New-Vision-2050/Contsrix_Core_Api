<?php

declare(strict_types=1);

namespace Modules\UserInfo\JobOffer\DTO;

use Ramsey\Uuid\UuidInterface;
use Illuminate\Http\UploadedFile;

class CreateJobOfferDTO
{
    public function __construct(
        public string $company_id,
        public string $global_id,
        public string $job_offer_number,
        public string $date_send,
        public string $date_accept,

    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id'=> $this->company_id,
            'global_id'=> $this->global_id,
            'job_offer_number'=> $this->job_offer_number,
            'date_send'=> $this->date_send,
            'date_accept'=> $this->date_accept,
        ];
    }
}


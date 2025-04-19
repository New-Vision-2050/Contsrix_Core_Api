<?php

declare(strict_types=1);

namespace Modules\UserInfo\JobOffer\Presenters;

use Modules\UserInfo\JobOffer\Models\JobOffer;
use BasePackage\Shared\Presenters\AbstractPresenter;

class JobOfferPresenter extends AbstractPresenter
{
    private JobOffer $jobOffer;

    public function __construct(JobOffer $jobOffer)
    {
        $this->jobOffer = $jobOffer;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->jobOffer->id,
            'job_offer_number' => $this->jobOffer->job_offer_number,
            'date_send' => $this->jobOffer->date_send,
            'date_accept' => $this->jobOffer->date_accept,
            'file_url' => $this->jobOffer->getFirstMedia('upload_offerjob')?->getFullUrl(),

        ];
    }
}

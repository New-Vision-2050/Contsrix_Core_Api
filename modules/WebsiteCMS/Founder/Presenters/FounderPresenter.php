<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\Founder\Presenters;

use Modules\WebsiteCMS\Founder\Models\Founder;
use BasePackage\Shared\Presenters\AbstractPresenter;

class FounderPresenter extends AbstractPresenter
{
    private Founder $founder;

    public function __construct(Founder $founder)
    {
        $this->founder = $founder;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->founder->id,
            'name' => $this->founder->name,
            "name_ar"=>$this->founder->getTranslation('name', 'ar'),
            "name_en"=>$this->founder->getTranslation('name', 'en'),
            'description' => $this->founder->description,
            "description_ar"=>$this->founder->getTranslation('description', 'ar'),
            "description_en"=>$this->founder->getTranslation('description', 'en'),
            'job_title' => $this->founder->job_title,
            "job_title_ar"=>$this->founder->getTranslation('job_title', 'ar'),
            "job_title_en"=>$this->founder->getTranslation('job_title', 'en'),
            'personal_photo' => $this->founder->getFirstMediaUrl('personal_photo'),
            "status"=>$this->founder->status,
        ];
    }
}

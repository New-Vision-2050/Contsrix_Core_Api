<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTermAndCondition\Presenters;

use Modules\WebsiteCMS\WebsiteTermAndCondition\Models\WebsiteTermAndCondition;
use BasePackage\Shared\Presenters\AbstractPresenter;

class WebsiteTermAndConditionPresenter extends AbstractPresenter
{
    private WebsiteTermAndCondition $websiteTermAndCondition;

    public function __construct(WebsiteTermAndCondition $websiteTermAndCondition)
    {
        $this->websiteTermAndCondition = $websiteTermAndCondition;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->websiteTermAndCondition->id,
            'content' => $this->websiteTermAndCondition->content,
            "created_at"=>$this->websiteTermAndCondition->created_at,
            "updated_at"=>$this->websiteTermAndCondition->updated_at,
        ];
    }
}

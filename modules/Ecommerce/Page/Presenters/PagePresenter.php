<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Page\Presenters;

use Modules\Ecommerce\Page\Models\Page;
use BasePackage\Shared\Presenters\AbstractPresenter;

class PagePresenter extends AbstractPresenter
{
    private Page $page;

    public function __construct(Page $page)
    {
        $this->page = $page;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->page->id,
            'description' => $isListing 
                ? $this->page->description 
                : [
                    'ar' => $this->page->getTranslation('description', 'ar'),
                    'en' => $this->page->getTranslation('description', 'en'),
                ],
            'type' => $this->page->type,
            'company_id' => $this->page->company_id,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Page\Presenters;

use Modules\Ecommerce\Page\Models\Page;
use BasePackage\Shared\Presenters\AbstractPresenter;

class PageWebsitePresenter extends AbstractPresenter
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
            'description' =>  $this->page->description ?? '',
            'type' => $this->page->type,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\CategoryWebsiteCMS\Presenters;

use Modules\WebsiteCMS\CategoryWebsiteCMS\Enum\CategoryWebsiteCMSType;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Models\CategoryWebsiteCMS;
use BasePackage\Shared\Presenters\AbstractPresenter;

class CategoryWebsiteCMSPresenter extends AbstractPresenter
{
    private CategoryWebsiteCMS $categoryWebsiteCMS;

    public function __construct(CategoryWebsiteCMS $categoryWebsiteCMS)
    {
        $this->categoryWebsiteCMS = $categoryWebsiteCMS;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->categoryWebsiteCMS->id,
            'name' => $this->categoryWebsiteCMS->name,
            'name_ar' => $this->categoryWebsiteCMS->getTranslation('name', 'ar'),
            'name_en' => $this->categoryWebsiteCMS->getTranslation('name', 'en'),
            'category_type' => [
                "id"=>$this->categoryWebsiteCMS->category_type,
                "name"=>CategoryWebsiteCMSType::lang($this->categoryWebsiteCMS->category_type)
            ],

            'company_id' => $this->categoryWebsiteCMS->company_id,
            'created_at' => $this->categoryWebsiteCMS->created_at,
            'updated_at' => $this->categoryWebsiteCMS->updated_at,
        ];
    }
}

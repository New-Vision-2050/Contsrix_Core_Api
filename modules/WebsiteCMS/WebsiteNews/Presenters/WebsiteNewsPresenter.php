<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteNews\Presenters;

use Modules\WebsiteCMS\WebsiteNews\Models\WebsiteNews;
use BasePackage\Shared\Presenters\AbstractPresenter;

class WebsiteNewsPresenter extends AbstractPresenter
{
    private WebsiteNews $websiteNews;

    public function __construct(WebsiteNews $websiteNews)
    {
        $this->websiteNews = $websiteNews;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->websiteNews->id,
            'title' => $this->websiteNews->title,
            'title_ar' => $this->websiteNews->getTranslation('title', 'ar'),
            'title_en' => $this->websiteNews->getTranslation('title', 'en'),
            'content' => $this->websiteNews->content,
            'content_ar' => $this->websiteNews->getTranslation('content', 'ar'),
            'content_en' => $this->websiteNews->getTranslation('content', 'en'),
            'main_image' => $this->websiteNews->getFirstMediaUrl('main_image'),
            'thumbnail' => $this->websiteNews->getFirstMediaUrl('thumbnail'),
            'category_website_cms_id' => $this->websiteNews->category_website_cms_id,
            'category' => $this->websiteNews->category ? [
                'id' => $this->websiteNews->category->id,
                'name' => $this->websiteNews->category->name,
            ] : null,
            'publish_date' => $this->websiteNews->publish_date?->format('Y-m-d'),
            'end_date' => $this->websiteNews->end_date?->format('Y-m-d'),
            'status' => $this->websiteNews->status,
            'created_at' => $this->websiteNews->created_at?->toIso8601String(),
            'updated_at' => $this->websiteNews->updated_at?->toIso8601String(),
        ];
    }
}

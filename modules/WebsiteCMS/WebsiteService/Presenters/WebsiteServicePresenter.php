<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteService\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\WebsiteCMS\WebsiteService\Models\WebsiteService;

class WebsiteServicePresenter extends AbstractPresenter
{
    public function __construct(private WebsiteService $service)
    {
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->service->id,
            'name' => $this->service->name,
            'name_ar' => $this->service->getTranslation('name', 'ar'),
            'name_en' => $this->service->getTranslation('name', 'en'),
            'main_image' => $this->service->getFirstMediaUrl('main_image'),
            'icon' => $this->service->getFirstMediaUrl('icon'),
            'category_website_cms_id' => $this->service->category_website_cms_id,
            'category' => $this->service->category ? [
                'id' => $this->service->category->id,
                'name' => $this->service->category->name,
                'name_ar' => $this->service->category->getTranslation('name', 'ar'),
                'name_en' => $this->service->category->getTranslation('name', 'en'),
            ] : null,
            'reference_number' => $this->service->reference_number,
            'description' => $this->service->description,
            'description_ar' => $this->service->getTranslation('description', 'ar'),
            'description_en' => $this->service->getTranslation('description', 'en'),
            'previous_work' => $this->service->previousWorks->map(function ($work) {
                return [
                    'id' => $work->id,
                    'description' => $work->description,
                    'image' => $work->getFirstMediaUrl('image'),
                ];
            })->toArray(),
            'company_id' => $this->service->company_id,
            'created_at' => $this->service->created_at?->toDateTimeString(),
            'updated_at' => $this->service->updated_at?->toDateTimeString(),
        ];
    }
}

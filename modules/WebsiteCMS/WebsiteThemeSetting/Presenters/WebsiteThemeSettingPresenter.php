<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteThemeSetting\Presenters;

use Modules\WebsiteCMS\WebsiteThemeSetting\Models\WebsiteThemeSetting;
use BasePackage\Shared\Presenters\AbstractPresenter;

class WebsiteThemeSettingPresenter extends AbstractPresenter
{
    private WebsiteThemeSetting $websiteThemeSetting;

    public function __construct(WebsiteThemeSetting $websiteThemeSetting)
    {
        $this->websiteThemeSetting = $websiteThemeSetting;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->websiteThemeSetting->id,
            'title' => $this->websiteThemeSetting->title,
            'description' => $this->websiteThemeSetting->description,
            'about' => $this->websiteThemeSetting->about,
            'is_default' => $this->websiteThemeSetting->is_default,
            'status' => $this->websiteThemeSetting->status,
            'main_image' => $this->websiteThemeSetting->relationLoaded('media')
                ? ($this->websiteThemeSetting->getMedia('main_image')->first()?->getUrl() ?? null)
                : $this->websiteThemeSetting->getFirstMediaUrl('main_image'),
            'created_at' => $this->websiteThemeSetting->created_at?->toDateTimeString(),
            'updated_at' => $this->websiteThemeSetting->updated_at?->toDateTimeString(),
        ];

        // Add departments if loaded
        if ($this->websiteThemeSetting->relationLoaded('departments')) {
            $data['departments'] = $this->websiteThemeSetting->departments->map(function ($department) {
                return [
                    'id' => $department->id,
                    'name' => $department->name,
                    'created_at' => $department->created_at?->toDateTimeString(),
                ];
            })->toArray();
        }

        return $data;
    }
}

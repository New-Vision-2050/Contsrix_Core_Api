<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAboutUs\Presenters;

use Modules\WebsiteCMS\WebsiteAboutUs\Models\WebsiteAboutUs;
use BasePackage\Shared\Presenters\AbstractPresenter;

class WebsiteAboutUsWebsitePresenter extends AbstractPresenter
{
    private WebsiteAboutUs $websiteAboutUs;

    public function __construct(WebsiteAboutUs $websiteAboutUs)
    {
        $this->websiteAboutUs = $websiteAboutUs;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->websiteAboutUs->id,
            'company_id' => $this->websiteAboutUs->company_id,
            'title' =>$this->websiteAboutUs->title,
            'description' => $this->websiteAboutUs->description,
            'is_certificates' => $this->websiteAboutUs->is_certificates,
            'is_approvals' => $this->websiteAboutUs->is_approvals,
            'is_companies' => $this->websiteAboutUs->is_companies,
            'about_me' => $this->websiteAboutUs->about_me,

            'vision' => $this->websiteAboutUs->vision,
            'target' => $this->websiteAboutUs->target,
            'slogan' => $this->websiteAboutUs->slogan,
            'status' => $this->websiteAboutUs->status,
            'created_at' => $this->websiteAboutUs->created_at?->toDateTimeString(),
            'updated_at' => $this->websiteAboutUs->updated_at?->toDateTimeString(),
        ];

        // Add main image if exists
        $mainImage = $this->websiteAboutUs->getFirstMediaUrl('main_image');
        $data['main_image'] = $mainImage ?: null;

        // Add project types if not listing or if loaded
        if (!$isListing && $this->websiteAboutUs->relationLoaded('projectTypes')) {
            $data['project_types'] = $this->websiteAboutUs->projectTypes->map(function ($projectType) {
                return [
                    'id' => $projectType->id,
                    'title' => $projectType->title,
                    'count' => $projectType->count,
                ];
            })->toArray();
        }

        // Add attachments if not listing or if loaded
        if (!$isListing && $this->websiteAboutUs->relationLoaded('attachments')) {
            $data['attachments'] = $this->websiteAboutUs->attachments->map(function ($attachment) {
                return [
                    'id' => $attachment->id,
                    'name' => $attachment->name,
                    'attachment_url' => $attachment->getFirstMediaUrl('attachment') ?: null,
                ];
            })->toArray();
        }



        // Add company icons if loaded
        if ($this->websiteAboutUs->relationLoaded('companyIcons')) {
            $data['company_icons'] = $this->websiteAboutUs->companyIcons->map(function ($icon) {
                return [
                    'id' => $icon->id,
                    'name' => $icon->name,
                    'category_type' => $icon->website_icon_category_type->value,
                    'icon_url' => $icon->getFirstMediaUrl('icon') ?: null,
                ];
            })->toArray();
        }else{
            $data['company_icons'] = [];
        }

        return $data;
    }
}

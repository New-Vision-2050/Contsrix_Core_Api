<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAboutUs\Presenters;

use Modules\WebsiteCMS\WebsiteAboutUs\Models\WebsiteAboutUs;
use BasePackage\Shared\Presenters\AbstractPresenter;

class WebsiteAboutUsPresenter extends AbstractPresenter
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
            'about_me' => [
                'ar' => $this->websiteAboutUs->getTranslation('about_me', 'ar'),
                'en' => $this->websiteAboutUs->getTranslation('about_me', 'en'),
            ],
            'vision' => [
                'ar' => $this->websiteAboutUs->getTranslation('vision', 'ar'),
                'en' => $this->websiteAboutUs->getTranslation('vision', 'en'),
            ],
            'target' => [
                'ar' => $this->websiteAboutUs->getTranslation('target', 'ar'),
                'en' => $this->websiteAboutUs->getTranslation('target', 'en'),
            ],
            'slogan' => [
                'ar' => $this->websiteAboutUs->getTranslation('slogan', 'ar'),
                'en' => $this->websiteAboutUs->getTranslation('slogan', 'en'),
            ],
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
                    'title' => [
                        'ar' => $projectType->getTranslation('title', 'ar'),
                        'en' => $projectType->getTranslation('title', 'en'),
                    ],
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

        return $data;
    }
}

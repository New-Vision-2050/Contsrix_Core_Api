<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProject\Presenters;

use Modules\WebsiteCMS\WebsiteProject\Models\WebsiteProject;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\WebsiteCMS\WebsiteService\Presenters\WebsiteServicePresenter;

class WebsiteProjectPresenter extends AbstractPresenter
{
    private WebsiteProject $websiteProject;

    public function __construct(WebsiteProject $websiteProject)
    {
        $this->websiteProject = $websiteProject;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->websiteProject->id,
            'name' => $this->websiteProject->name,
            'website_project_setting_id'=>$this->websiteProject->website_project_setting_id,
            'website_project_setting'=>$this->websiteProject->websiteProjectSetting,
            'name_ar'=>$this->websiteProject->getTranslation('name', 'ar'),
            'name_en'=>$this->websiteProject->getTranslation('name', 'en'),
            'title'=>$this->websiteProject->title,
            'title_ar'=>$this->websiteProject->getTranslation('title', 'ar'),
            'title_en'=>$this->websiteProject->getTranslation('title', 'en'),
            'description' => $this->websiteProject->description,
            "description_ar"=>$this->websiteProject->getTranslation('description', 'ar'),
            "description_en"=>$this->websiteProject->getTranslation('description', 'en'),
            'status' => $this->websiteProject->status,
            'created_at' => $this->websiteProject->created_at,
            'updated_at' => $this->websiteProject->updated_at,
            'main_image' => $this->websiteProject->getFirstMediaUrl('main_image'),
            'secondary_images' => $this->websiteProject->getMedia('secondary_images')?->map(fn($media) => $media->getUrl())->toArray(),
            'project_details' => $this->websiteProject->projectDetails?->map(function ($projectDetail) {
                return [
                    'id' => $projectDetail->id,
                    'name' => $projectDetail->name,
                    'name_ar' => $projectDetail->getTranslation('name', 'ar'),
                    'name_en' => $projectDetail->getTranslation('name', 'en'),
                    "website_service_id"=>$projectDetail->website_service_id,
                    "website_service"=>(new WebsiteServicePresenter($projectDetail->websiteService))->getData(),
                    "website_project_id"=>$projectDetail->website_project_id,
                    'created_at' => $projectDetail->created_at,
                    'updated_at' => $projectDetail->updated_at,
                ];
            }),
            'services' => $this->websiteProject->services,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteOurService\Presenters;

use Modules\WebsiteCMS\WebsiteOurService\Models\WebsiteOurService;
use BasePackage\Shared\Presenters\AbstractPresenter;

class WebsiteOurServicePresenter extends AbstractPresenter
{
    private WebsiteOurService $websiteOurService;

    public function __construct(WebsiteOurService $websiteOurService)
    {
        $this->websiteOurService = $websiteOurService;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->websiteOurService->id,
            'title' => $this->websiteOurService->title,
            'description' => $this->websiteOurService->description,
            'status' => $this->websiteOurService->status,
            'company_id' => $this->websiteOurService->company_id,
            'created_at' => $this->websiteOurService->created_at?->toDateTimeString(),
            'updated_at' => $this->websiteOurService->updated_at?->toDateTimeString(),
        ];

        if (!$isListing && $this->websiteOurService->relationLoaded('departments')) {
            $data['departments'] = $this->websiteOurService->departments->map(function ($department) {
                return [
                    'id' => $department->id,
                    'title' => $department->title,
                    'description' => $department->description,
                    'type' => $department->type->value,
                    'website_services' => $department->websiteServices->map(function ($service) {
                        return [
                            'id' => $service->id,
                            'name' => $service->name,
                            'reference_number' => $service->reference_number,
                        ];
                    })->toArray(),
                ];
            })->toArray();
        }

        return $data;
    }
}

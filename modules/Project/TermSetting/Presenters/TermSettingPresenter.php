<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\Presenters;

use Modules\Project\TermSetting\Models\TermSetting;
use BasePackage\Shared\Presenters\AbstractPresenter;

class TermSettingPresenter extends AbstractPresenter
{
    private TermSetting $termSetting;

    public function __construct(TermSetting $termSetting)
    {
        $this->termSetting = $termSetting;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->termSetting->id,
            'name' => $this->termSetting->name,
            'description' => $this->termSetting->description,
            'parent_id' => $this->termSetting->parent_id,
            'project_type_id' => $this->termSetting->project_type_id,
            'is_active' => $this->termSetting->is_active,
            'children_count' => $this->termSetting->children()->count(),
            "services" => $this->termSetting->termServices,
            'term_services_count' => $this->termSetting->termServices()->count(),
            'created_at' => $this->termSetting->created_at?->toDateTimeString(),
            'updated_at' => $this->termSetting->updated_at?->toDateTimeString(),
        ];

        if (!$isListing && $this->termSetting->relationLoaded('projectType') && $this->termSetting->projectType) {
            $data['project_type'] = [
                'id' => $this->termSetting->projectType->id,
                'name' => $this->termSetting->projectType->name,
            ];
        }

        if (!$isListing && $this->termSetting->relationLoaded('children')) {
            $data['children'] = $this->termSetting->children->map(function ($child) {
                return [
                    'id' => $child->id,
                    'name' => $child->name,
                    'parent_id' => $child->parent_id,
                    'is_active' => $child->is_active,
                    'children_count' => $child->children()->count(),
                ];
            });
        }

        if (!$isListing && $this->termSetting->relationLoaded('termServices')) {
            $data['term_services'] = $this->termSetting->termServices->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'is_active' => $service->is_active,
                ];
            });
        }

        return $data;
    }
}

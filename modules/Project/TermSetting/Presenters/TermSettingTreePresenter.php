<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\Presenters;

use Modules\Project\TermSetting\Models\TermSetting;
use BasePackage\Shared\Presenters\AbstractPresenter;

class TermSettingTreePresenter extends AbstractPresenter
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
        ];

        if ($this->termSetting->relationLoaded('children') && $this->termSetting->children->isNotEmpty()) {
            $data['children'] = $this->formatChildren($this->termSetting->children);
        } else {
            $data['children'] = [];
        }

        return $data;
    }

    private function formatChildren($children): array
    {
        $formatted = [];

        foreach ($children as $child) {
            $formatted[] = [
                'id' => $child->id,
                'name' => $child->name,
                'description' => $child->description,
                'parent_id' => $child->parent_id,
                'project_type_id' => $child->project_type_id,
                'is_active' => $child->is_active,
                'children' => $child->relationLoaded('children') && $child->children->isNotEmpty()
                    ? $this->formatChildren($child->children)
                    : [],
            ];
        }

        return $formatted;
    }
}

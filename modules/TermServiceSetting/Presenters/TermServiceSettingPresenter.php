<?php

declare(strict_types=1);

namespace Modules\TermServiceSetting\Presenters;

use Modules\TermServiceSetting\Models\TermServiceSetting;
use BasePackage\Shared\Presenters\AbstractPresenter;

class TermServiceSettingPresenter extends AbstractPresenter
{
    private TermServiceSetting $termServiceSetting;

    public function __construct(TermServiceSetting $termServiceSetting)
    {
        $this->termServiceSetting = $termServiceSetting;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->termServiceSetting->id,
            'name' => $this->termServiceSetting->name,
            'created_at' => $this->termServiceSetting->created_at?->toDateTimeString(),
            'updated_at' => $this->termServiceSetting->updated_at?->toDateTimeString(),
        ];

        if (!$isListing && $this->termServiceSetting->relationLoaded('termSettings')) {
            if ($this->termServiceSetting->termSettings->isNotEmpty()) {
                $data['terms'] = $this->formatTermsHierarchy();
            } else {
                $data['terms'] = [];
            }
        }

        return $data;
    }

    private function formatTermsHierarchy(): array
    {
        $trees = [];
        
        foreach ($this->termServiceSetting->termSettings as $termSetting) {
            $rootTerm = $this->getRootTerm($termSetting);
            
            $rootId = $rootTerm->id;
            if (!isset($trees[$rootId])) {
                $trees[$rootId] = $this->buildTermTree($rootTerm);
            }
        }

        return array_values($trees);
    }

    private function getRootTerm($termSetting)
    {
        $current = $termSetting;
        while ($current->parent) {
            $current = $current->parent;
        }
        return $current;
    }

    private function buildTermTree($termSetting): array
    {
        $tree = [
            'id' => $termSetting->id,
            'name' => $termSetting->name,
            'description' => $termSetting->description,
            'parent_id' => $termSetting->parent_id,
            'is_active' => $termSetting->is_active,
        ];

        if ($termSetting->relationLoaded('children') && $termSetting->children->isNotEmpty()) {
            $tree['children'] = [];
            foreach ($termSetting->children as $child) {
                $tree['children'][] = $this->buildTermTree($child);
            }
        } else {
            $children = $termSetting->children()->with('children')->get();
            if ($children->isNotEmpty()) {
                $tree['children'] = [];
                foreach ($children as $child) {
                    $tree['children'][] = $this->buildTermTree($child);
                }
            } else {
                $tree['children'] = [];
            }
        }

        return $tree;
    }
}

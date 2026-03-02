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

        if (!$isListing) {
            // Build tree from leaf terms stored in termSettings relationship
            if ($this->termServiceSetting->relationLoaded('termSettings')) {
                if ($this->termServiceSetting->termSettings->isNotEmpty()) {
                    $data['children'] = $this->formatTermsHierarchyFromLeafs();
                } else {
                    $data['children'] = [];
                }
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

    private function formatTermsHierarchyFromLeafs(): array
    {
        $trees = [];
        $leafTerms = $this->termServiceSetting->termSettings;

        foreach ($leafTerms as $leafTerm) {
            // Build the path from leaf to root
            $path = $this->buildPathToRoot($leafTerm);
            
            // Build the tree from this path
            $this->mergePathIntoTree($trees, $path);
        }

        return array_values($trees);
    }

    private function buildPathToRoot($term): array
    {
        $path = [];
        $current = $term;
        
        // Build path from leaf to root
        while ($current) {
            array_unshift($path, $current);
            $current = $current->parent;
        }
        
        return $path;
    }

    private function mergePathIntoTree(&$trees, array $path)
    {
        if (empty($path)) return;
        
        $rootId = $path[0]->id;
        
        // If root doesn't exist, create it
        if (!isset($trees[$rootId])) {
            $trees[$rootId] = $this->buildTermTree($path[0]);
        }
        
        // Merge the rest of the path
        $currentNode = &$trees[$rootId];
        for ($i = 1; $i < count($path); $i++) {
            $term = $path[$i];
            $found = false;
            
            // Check if this child already exists
            if (isset($currentNode['children'])) {
                foreach ($currentNode['children'] as &$child) {
                    if ($child['id'] == $term->id) {
                        $currentNode = &$child;
                        $found = true;
                        break;
                    }
                }
            }
            
            // If not found, add it
            if (!$found) {
                if (!isset($currentNode['children'])) {
                    $currentNode['children'] = [];
                }
                $newChild = $this->buildTermTree($term);
                $currentNode['children'][] = &$newChild;
                $currentNode = &$newChild;
            }
        }
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

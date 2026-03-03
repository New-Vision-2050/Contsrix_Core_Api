<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Presenters;

use Modules\Project\ProjectType\Models\ProjectType;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ProjectTypePresenter extends AbstractPresenter
{
    private ProjectType $projectType;

    public function __construct(ProjectType $projectType)
    {
        $this->projectType = $projectType;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->projectType->id,
            'name' => $this->projectType->name,
            'icon' => $this->projectType->icon,
            'parent_id' => $this->projectType->parent_id,
            'is_created' => $this->projectType->is_created,
            'is_have_schema' => $this->projectType->is_have_schema,
            'is_active' => $this->projectType->is_active,
            'path' => $this->projectType->path,
        ];

        if (!$isListing) {
            $data['parent'] = $this->projectType->parent ? [
                'id' => $this->projectType->parent->id,
                'name' => $this->projectType->parent->name,
            ] : null;

            $data['children'] = $this->projectType->children->map(function ($child) {
                return [
                    'id' => $child->id,
                    'name' => $child->name,
                    'icon' => $child->icon,
                    'is_have_schema' => $child->is_have_schema,
                ];
            })->toArray();

            if ($this->projectType->is_have_schema && $this->projectType->relationLoaded('activeSchemas')) {
                $data['schemas'] = $this->projectType->activeSchemas->map(function ($schema) {
                    return [
                        'id' => $schema->id,
                        'name' => $schema->name,
                        'field_type' => $schema->field_type,
                        'is_required' => $schema->is_required,
                        'options' => $schema->options,
                        'order' => $schema->order,
                    ];
                })->toArray();
            }
        }

        return $data;
    }
}

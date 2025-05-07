<?php

declare(strict_types=1);

namespace Modules\SubEntity\Presenters;

use Modules\SubEntity\Models\SubEntity;
use BasePackage\Shared\Presenters\AbstractPresenter;

class SubEntityPresenter extends AbstractPresenter
{
    private SubEntity $subEntity;

    public function __construct(SubEntity $subEntity)
    {
        $this->subEntity = $subEntity;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->subEntity->id,
            'name' => $this->subEntity->name,
            'icon' => $this->subEntity->icon,
            'super_entity' => $this->subEntity->super_entity,
            'is_active' => $this->subEntity->is_active,
            'is_registrable' => $this->subEntity->is_registrable,
            'main_program' => $this->subEntity->mainProgram->name ?? null,
            'main_program_id' => $this->subEntity->main_program_id,
            'default_attributes' => $this->subEntity->default_attributes
                ? json_decode($this->subEntity->default_attributes, true)
                : [],
            'optional_attributes' => $this->subEntity->optional_attributes
                ? json_decode($this->subEntity->optional_attributes, true)
                : null,
            'created_at' => $this->subEntity->created_at?->toIso8601String(),
            'updated_at' => $this->subEntity->updated_at?->toIso8601String(),
        ];
    }

    public function getData(bool $isListing = false): ?array
    {
        return $this->present($isListing);
    }
}

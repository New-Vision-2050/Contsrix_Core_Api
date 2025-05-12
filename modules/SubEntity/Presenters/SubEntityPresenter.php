<?php

declare(strict_types=1);

namespace Modules\SubEntity\Presenters;

use Modules\SubEntity\Models\SubEntity;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Program\Presenters\ProgramPresenter;
use Modules\SubEntity\Services\AttributesTranslationService;
use Modules\SubEntity\Services\SuperEntityService;

class SubEntityPresenter extends AbstractPresenter
{
    private SubEntity $subEntity;
    private SuperEntityService $superEntityService;

    public function __construct(SubEntity $subEntity)
    {
        $this->subEntity = $subEntity;

        $this->superEntityService = app(SuperEntityService::class);
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->subEntity->id,
            'name' => $this->subEntity->name,
            'icon' => $this->subEntity->icon,
            'super_entity' => $this->getSuperEntity($this->subEntity->super_entity),
            'is_active' => $this->subEntity->is_active,
            'is_registrable' => $this->subEntity->is_registrable,
            'main_program' => $this->getMainProgram(),
            'default_attributes' => $this->formatAttributes($this->subEntity->default_attributes),
            'optional_attributes' => $this->formatAttributes($this->subEntity->optional_attributes),
            'attributes_count' => $this->subEntity->attributes_count,
            'usage_count' => 0,
            'created_at' => $this->subEntity->created_at?->toIso8601String(),
            'updated_at' => $this->subEntity->updated_at?->toIso8601String(),
        ];
    }

    public function getData(bool $isListing = false): ?array
    {
        return $this->present($isListing);
    }

    public function getAttributes(bool $isListing = false): ?array
    {
        return [
            'id' => $this->subEntity->id,
            'default_attributes' => $this->formatAttributes($this->subEntity->default_attributes),
            'optional_attributes' => $this->formatAttributes($this->subEntity->optional_attributes),
        ];
    }

    protected function formatAttributes(array|string|null $attributes)
    {
        if(empty($attributes)) {
            return [];
        }

        if (!is_array($attributes)) {
            $attributes = json_decode($attributes);
        }

        return array_map(function($name) {
            return AttributesTranslationService::getTranslations($name);
        }, $attributes);
    }

    public function getSuperEntity(string $id): ?array
    {
        $superEntity = $this->superEntityService->getById($id);

        if($superEntity) {
            $presenter = new SuperEntityPresenter($superEntity);

            return $presenter->getData();
        }

        return [];
    }

    public function getMainProgram(): ?array
    {
        $presenter = new ProgramPresenter($this->subEntity->mainProgram);

        return $presenter->getData();
    }

    public function getForSelection(): ?array
    {
        return [
            'id' => $this->subEntity->id,
            'name' => $this->subEntity->name
        ];
    }

    public static function selectionCollection(iterable $collection, ...$additionalParams): array
    {
        $result = [];
        foreach ($collection as $item) {
            $data = (new static($item, ...$additionalParams))->getForSelection();

            if ($data === null) {
                continue;
            }

            $result[] = $data;
        }

        return $result;
    }
}

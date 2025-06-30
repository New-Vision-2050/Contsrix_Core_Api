<?php

declare(strict_types=1);

namespace Modules\SubEntity\Presenters;

use Modules\SubEntity\Models\SubEntity;
use Modules\Program\Presenters\ProgramPresenter;
use Modules\SubEntity\Services\SuperEntityService;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Company\CompanyCore\Models\Company;
use Modules\SubEntity\Presenters\RegistrationFormPresenter;
use Modules\SubEntity\Services\AttributesTranslationService;

class SubEntityPresenter extends AbstractPresenter
{
    private SubEntity $subEntity;
    private int $companiesCount;
    private SuperEntityService $superEntityService;

    public function __construct(SubEntity $subEntity)
    {
        $this->subEntity = $subEntity;

        $this->superEntityService = app(SuperEntityService::class);

        $this->companiesCount = Company::count();
    }

    public function getFeatures(): array
    {
        return $this->subEntity->features->map(function ($feature) {
            return [
                'id' => $feature->id,
                'name' => $feature->name,
                'slug' => $feature->slug,
                'created_at' => $feature->created_at?->toIso8601String(),
            ];
        })->toArray();
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->subEntity->id,
            'name' => $this->subEntity->name,
            'slug' => $this->subEntity->slug,
            'icon' => $this->subEntity->icon,
            'origin_super_entity' => $this->subEntity->origin_super_entity,
            'super_entity' => $this->getSuperEntity($this->subEntity->super_entity),
            'is_active' => $this->subEntity->is_active,
            'is_registrable' => $this->subEntity->is_registrable,
            'main_program' => $this->getMainProgram(),
            'registration_form' => $this->getRegistrationForm(),
            'default_attributes' => $this->formatAttributes($this->subEntity->default_attributes),
            'optional_attributes' => $this->formatAttributes($this->subEntity->optional_attributes),
            'attributes_count' => $this->subEntity->attributes_count,
            'usage_count' => $this->companiesCount,
            'features' => $this->getFeatures(),
            'created_at' => $this->subEntity->created_at?->toIso8601String(),
            'updated_at' => $this->subEntity->updated_at?->toIso8601String(),
        ];
    }

    public function getData(bool $isListing = false): ?array
    {
        $present = $this->present($isListing);
        $allowedRegistrationForms = $this->subEntity->allowedChildForms;

        $present['allowed_registration_forms'] = filled($allowedRegistrationForms) ? RegistrationFormPresenter::collection($allowedRegistrationForms) : [];
        return $present;
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
        if (empty($attributes)) {
            return [];
        }

        if (!is_array($attributes)) {
            $attributes = json_decode($attributes);
        }

        return array_map(function ($name) {
            return AttributesTranslationService::getTranslations($name);
        }, $attributes);
    }

    public function getSuperEntity(string $id): ?array
    {
        $superEntity = $this->superEntityService->getById($id);

        if ($superEntity) {
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

    public function getRegistrationForm()
    {
        $presenter = new RegistrationFormPresenter($this->subEntity->registrationForm);

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

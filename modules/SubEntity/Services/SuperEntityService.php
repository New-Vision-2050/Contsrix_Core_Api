<?php

declare(strict_types=1);

namespace Modules\SubEntity\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Modules\SubEntity\Repositories\SuperEntityRepository;

class SuperEntityService
{
    public function __construct(
        private SuperEntityRepository $repository,
        private SubEntityCRUDService $subEntityCRUDService,
    ) {
    }

    public function list(?string $search = ''): array
    {
        $supEntities = $this->subEntityCRUDService->getSuperEntitySelection();
        $supEntities = $supEntities['data']->toArray();
        $superEntities = $this->repository->list($search);

        return array_merge($supEntities, $superEntities);
    }

    public function getAllAttributesForSelection(string $superEntityId): array
    {
        $id = $superEntityId;
        $attributes = [];
        if (Str::isUuid($id)) {
            $parentSubEntity = $this->subEntityCRUDService->get(id: Uuid::fromString($id));
            $attributes = array_merge($parentSubEntity->default_attributes, $parentSubEntity->optional_attributes ?? []);
        } else {
            $attributes = $this->repository->getAvailableAttributes($id) ?? [];
        }

        return array_map(function ($name) {
            return AttributesTranslationService::getTranslations($name);
        }, $attributes);
    }

    public function getDefaultAttributes(string $superEntityId): array
    {
        $id = $superEntityId;
        $attributes = [];
        if (Str::isUuid($id)) {
            $parentSubEntity = $this->subEntityCRUDService->get(id: Uuid::fromString($id));
            $attributes = array_merge($parentSubEntity->default_attributes, $parentSubEntity->optional_attributes ?? []);
        } else {
            $attributes = $this->repository->getAvailableAttributes($id) ?? [];
        }

        $attributesConfig = $this->getAttributesConfig($superEntityId);
        $defaultAttrubutes = $attributesConfig['default_attributes'] ?? $attributes;

        return array_map(function ($name) {
            return AttributesTranslationService::getTranslations($name);
        }, $defaultAttrubutes);
    }
    public function getOptionalAttributes(string $superEntityId): array
    {
        $id = $superEntityId;
        $attributes = [];
        if (Str::isUuid($id)) {
            $parentSubEntity = $this->subEntityCRUDService->get(id: Uuid::fromString($id));
            $attributes = array_merge($parentSubEntity->default_attributes, $parentSubEntity->optional_attributes ?? []);
        } else {
            $attributes = $this->repository->getAvailableAttributes($id) ?? [];
        }

        $attributesConfig = $this->getAttributesConfig($superEntityId);
        $defaultAttrubutes = $attributesConfig['optional_attributes'] ?? $attributes;

        return array_map(function ($name) {
            return AttributesTranslationService::getTranslations($name);
        }, $defaultAttrubutes);
    }

    public function getAllAttributes(string $superEntityId): array
    {
        $id = $superEntityId;
        $attributes = [];
        if (Str::isUuid($id)) {
            $parentSubEntity = $this->subEntityCRUDService->get(id: Uuid::fromString($id));
            $attributes = array_values(array_unique(array_merge($parentSubEntity->default_attributes ?? [], $parentSubEntity->optional_attributes ?? [])));
        } else {
            $attributes = $this->repository->getAvailableAttributes($id) ?? [];
        }

        return $attributes;
    }

    public function getAttributesConfig(string $superEntityId): array
    {
        return [
            'default_attributes' => $this->repository->getConfigValue($superEntityId, 'default_attributes') ?? $this->getAllAttributes($superEntityId),
            'optional_attributes' => $this->repository->getConfigValue($superEntityId, 'optional_attributes') ?? $this->getAllAttributes($superEntityId),
        ];
    }

    public function getRegistrationConfig(string $superEntityId): array
    {
        return [
            'registration_forms' => $this->repository->getConfigValue($superEntityId, 'registration_forms') ?? $this->getDefaultRegistrationFormsIds($superEntityId),
            'is_registrable' => $this->repository->getConfigValue($superEntityId, 'is_registrable') ?? true
        ];
    }

    public function getConfig(string $superEntityId): array
    {
        return $this->repository->getConfig($superEntityId);
    }

    public function getIds()
    {
        return $this->repository->getIds();
    }

    public function getModelForId(string $id): ?string
    {
        $superEntityId = $id;

        while (Str::isUuid($superEntityId)) {
            $parentSubEntity = $this->subEntityCRUDService->find(Uuid::fromString($superEntityId));

            if (!$parentSubEntity) {
                break;
            }

            $superEntityId = $parentSubEntity->super_entity;
        }

        return $this->repository->getModelForId($superEntityId);
    }

    public function getRegistrationFormsForId(string $id): Collection
    {
        $superEntityId = $id;

        if (Str::isUuid($superEntityId)) {
            $parentSubEntity = $this->subEntityCRUDService->find(Uuid::fromString($superEntityId));
            $allowedRegistrationForms = $parentSubEntity->allowedChildForms;
            return filled($allowedRegistrationForms) ? $allowedRegistrationForms :
                $this->repository->getRegistrationFormsForId($parentSubEntity->origin_super_entity);
        }

        return $this->repository->getRegistrationFormsForId($id);
    }

    public function getDefaultRegistrationFormsIds(string $id): array
    {
        $superEntityId = $id;
        if (Str::isUuid($superEntityId)) {
            $parentSubEntity = $this->subEntityCRUDService->find(Uuid::fromString($superEntityId));
            $allowedRegistrationForms = $parentSubEntity->allowedChildForms;
            return filled($allowedRegistrationForms) ? $allowedRegistrationForms->pluck('id')->toArray() :
                $this->repository->getRegistrationFormsIds($parentSubEntity->origin_super_entity);
        }

        return $this->repository->getRegistrationFormsIds($id);
    }

    public function getById(string $id): ?array
    {
        $config = $this->getConfig($id);

        if (Str::isUuid($id)) {
            $parentSubEntity = $this->subEntityCRUDService->get(Uuid::fromString($id));
            return [
                'id' => $id,
                'name' => $parentSubEntity->name,
                'config' => $config
            ];
        }

        return array_merge($this->repository->getById($id), ['config' => $config]);
    }
}

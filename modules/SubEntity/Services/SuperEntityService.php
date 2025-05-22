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
        $supEntities = $this->subEntityCRUDService->getSelection();
        $supEntities = $supEntities['data']->toArray();
        $superEntities = $this->repository->list($search);
        return array_merge($supEntities, $superEntities);
    }

    public function getAvailableAttributes(string $superEntityId): array
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

    public function setAttributesConfig(string $superEntityId, $attributes): array
    {
        return $this->repository->setAttributesConfig($superEntityId, $attributes);
    }

    public function getAttributesConfig(string $superEntityId): array
    {
        $attributes = $this->repository->getAttributesConfig($superEntityId);

        if (isset($attributes['allowed_attributes']) && filled($attributes['allowed_attributes'])) {
            return array_map(function ($name) {
                return AttributesTranslationService::getTranslations($name);
            }, $attributes['allowed_attributes'] ?? []);
        }

        // fallback to the whole list of attributes
        return $this->getAvailableAttributes($superEntityId);
    }

    public function getRegistrationFormsConfig(string $superEntityId): array
    {
        return $this->repository->getConfigValue($superEntityId, 'registration_forms') ?? [];
    }

    public function getIsRegistrableConfig(string $superEntityId): bool
    {
        return $this->repository->getConfigValue($superEntityId, 'is_registrable') ?? [];
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

    public function getById(string $id): ?array
    {
        $allowed_attributes = $this->getAttributesConfig($id);

        if (Str::isUuid($id)) {
            $parentSubEntity = $this->subEntityCRUDService->get(Uuid::fromString($id));
            return [
                'id' => $id,
                'name' => $parentSubEntity->name,
                'allowed_attributes' => $allowed_attributes
            ];
        }

        return array_merge($this->repository->getById($id), ['allowed_attributes' => $allowed_attributes]);
    }
}

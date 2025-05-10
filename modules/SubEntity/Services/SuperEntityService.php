<?php

declare(strict_types=1);

namespace Modules\SubEntity\Services;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
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
        $subEntities = $this->repository->list($search);
        return array_merge($supEntities, $subEntities);
    }

    public function getAvailableAttributes(string $superEntityId): array
    {
        $id = $superEntityId;
        if (Str::isUuid($id)) {
            $parentSubEntity = $this->subEntityCRUDService->get(id: Uuid::fromString($id));
            $id = $parentSubEntity?->super_entity;
        }

        return array_map(function($name) {
            return AttributesTranslationService::getTranslations($name);
        }, $this->repository->getAvailableAttributes($id) ?? []);
    }

    public function getIds()
    {
        return $this->repository->getIds();
    }

    public function getModelForId(string $id): ?string
    {
        $superEntityId = $id;
        if (Str::isUuid($id)) {
            $parentSubEntity = $this->subEntityCRUDService->get(Uuid::fromString($id));
            $superEntityId = $parentSubEntity?->super_entity;
        }

        return $this->repository->getModelForId($superEntityId);
    }

    public function getById(string $id): ?array
    {
        if (Str::isUuid($id)) {
            $parentSubEntity = $this->subEntityCRUDService->get(Uuid::fromString($id));
            return [
                'id' => $id,
                'name' => $parentSubEntity->name
            ];
        }

        return $this->repository->getById($id);
    }
}

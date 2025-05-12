<?php

declare(strict_types=1);

namespace Modules\SubEntity\Services;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Ramsey\Uuid\UuidInterface;
use Modules\SubEntity\Models\SubEntity;
use Modules\SubEntity\DTO\CreateSubEntityDTO;
use Modules\SubEntity\Repositories\SubEntityRepository;

class SubEntityCRUDService
{
    public function __construct(
        private SubEntityRepository $repository,
    ) {
    }

    public function create(CreateSubEntityDTO $createSubEntityDTO): SubEntity
    {
        $data = $createSubEntityDTO->toArray();
        $superEntityId = $data['super_entity'];

        if( Str::isUuid($superEntityId) ) {
            $subEntityAsSup = $this->get(Uuid::fromString($superEntityId));
            $data['origin_super_entity'] = $subEntityAsSup->getOriginSuperEntityName();
        }else{
            $data['origin_super_entity'] = $superEntityId;
        }

        return $this->repository->createSubEntity($data);
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): SubEntity
    {
        return $this->repository->getSubEntity(
            id: $id,
        );
    }

    public function paginatedBySuperEntity(string $superEntityId,  ?string $programSlug = null, ?string $entityName = null, int $page = 1, int $perPage = 10): array
    {

        if( Str::isUuid($superEntityId) ) {
            $subEntityAsSup = $this->get(Uuid::fromString($superEntityId));
            $superEntityId = $subEntityAsSup->getOriginSuperEntityName();
        }

        return $this->repository->getPaginatedBySuperEntity(
            superEntityId: $superEntityId,
            programSlug: $programSlug,
            entityName: $entityName,
            page: $page,
            perPage: $perPage
        );
    }

    public function getSelection(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->getSelection(
            page: $page,
            perPage: $perPage
        );
    }

       /**
     * @param string $id
     *
     * @return mixed
     */
    public function find($id)
    {
        return $this->repository->find($id);
    }
}

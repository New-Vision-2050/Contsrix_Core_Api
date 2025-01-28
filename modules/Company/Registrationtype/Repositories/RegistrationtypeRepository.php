<?php

declare(strict_types=1);

namespace Modules\Company\RegistrationType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Company\RegistrationType\Models\RegistrationType;

/**
 * @property RegistrationType $model
 * @method RegistrationType findOneOrFail($id)
 * @method RegistrationType findOneByOrFail(array $data)
 */
class RegistrationTypeRepository extends BaseRepository
{
    public function __construct(RegistrationType $model)
    {
        parent::__construct($model);
    }

    public function getRegistrationTypeList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getRegistrationType(UuidInterface $id): RegistrationType
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createRegistrationType(array $data): RegistrationType
    {
        return $this->create($data);
    }

    public function updateRegistrationType(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteRegistrationType(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

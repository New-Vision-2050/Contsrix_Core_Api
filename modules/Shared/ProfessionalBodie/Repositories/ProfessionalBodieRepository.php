<?php

declare(strict_types=1);

namespace Modules\Shared\ProfessionalBodie\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\ProfessionalBodie\Models\ProfessionalBodie;

/**
 * @property ProfessionalBodie $model
 * @method ProfessionalBodie findOneOrFail($id)
 * @method ProfessionalBodie findOneByOrFail(array $data)
 */
class ProfessionalBodieRepository extends BaseRepository
{
    public function __construct(ProfessionalBodie $model)
    {
        parent::__construct($model);
    }

    public function getProfessionalBodieList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getProfessionalBodie(UuidInterface $id): ProfessionalBodie
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createProfessionalBodie(array $data): ProfessionalBodie
    {
        return $this->create($data);
    }

    public function updateProfessionalBodie(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteProfessionalBodie(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

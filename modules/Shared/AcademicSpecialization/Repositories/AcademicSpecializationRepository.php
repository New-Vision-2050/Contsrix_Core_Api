<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicSpecialization\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\AcademicSpecialization\Models\AcademicSpecialization;

/**
 * @property AcademicSpecialization $model
 * @method AcademicSpecialization findOneOrFail($id)
 * @method AcademicSpecialization findOneByOrFail(array $data)
 */
class AcademicSpecializationRepository extends BaseRepository
{
    public function __construct(AcademicSpecialization $model)
    {
        parent::__construct($model);
    }

    public function getAcademicSpecializationList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getAcademicSpecialization(UuidInterface $id): AcademicSpecialization
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createAcademicSpecialization(array $data): AcademicSpecialization
    {
        return $this->create($data);
    }

    public function updateAcademicSpecialization(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteAcademicSpecialization(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

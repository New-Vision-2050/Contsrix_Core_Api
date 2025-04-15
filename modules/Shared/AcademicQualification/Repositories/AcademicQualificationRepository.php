<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicQualification\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\AcademicQualification\Models\AcademicQualification;

/**
 * @property AcademicQualification $model
 * @method AcademicQualification findOneOrFail($id)
 * @method AcademicQualification findOneByOrFail(array $data)
 */
class AcademicQualificationRepository extends BaseRepository
{
    public function __construct(AcademicQualification $model)
    {
        parent::__construct($model);
    }

    public function getAcademicQualificationList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getAcademicQualification(UuidInterface $id): AcademicQualification
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createAcademicQualification(array $data): AcademicQualification
    {
        return $this->create($data);
    }

    public function updateAcademicQualification(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteAcademicQualification(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

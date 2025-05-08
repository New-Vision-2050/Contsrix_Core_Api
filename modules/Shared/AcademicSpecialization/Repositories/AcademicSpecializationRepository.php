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

    public function getAcademicSpecialization(UuidInterface $id): ?AcademicSpecialization
    {
        return $this->findOneBy([
            'id' => $id,
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

    public function paginated(
        array $conditions = [],
        int $page = 1,
        int $perPage = 15,
        string $orderBy = 'created_at',
        string $sortBy = 'desc'
    ) {
        if (method_exists($this->model, 'scopeFilter')) {
            $query = $this->model->filter(request()->all())->where($conditions);
        } else {
            $query = $this->model->where($conditions);
        }

        $query->orderByRaw("CASE WHEN EXISTS (
            SELECT 1 FROM translations
            WHERE translations.translatable_id = academic_specializations.id
            AND translations.content LIKE ?
        ) THEN 0 ELSE 1 END", ["%هندس%"]);

        // Normal ordering after matching
        $query->orderBy($orderBy, $sortBy);

        $count = $query->count();

        $paginatedData = $query
            ->forPage($page, $perPage)
            ->get();

        $paginationArray = $this->getPaginationInformation($page, $perPage, $count);

        return [
            'pagination' => $paginationArray['pagination'],
            'data' => $paginatedData,
        ];
    }


}

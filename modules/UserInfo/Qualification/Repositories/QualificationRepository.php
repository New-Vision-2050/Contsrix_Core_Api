<?php

declare(strict_types=1);

namespace Modules\UserInfo\Qualification\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\UserInfo\Qualification\Models\Qualification;

/**
 * @property Qualification $model
 * @method Qualification findOneOrFail($id)
 * @method Qualification findOneByOrFail(array $data)
 */
class QualificationRepository extends BaseRepository
{
    public function __construct(Qualification $model)
    {
        parent::__construct($model);
    }

    public function getQualificationList(UuidInterface $companyId, UuidInterface $globalId, ?int $page, ?int $perPage = 10)
    {
        return $this->paginated(
            ['company_id' => $companyId, 'global_id' => $globalId],
            $page,
            $perPage
        );
    }
    public function getQualification(UuidInterface $id): Qualification
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createQualification(array $data): Qualification
    {
        return $this->create($data);
    }

    public function updateQualification(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteQualification(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

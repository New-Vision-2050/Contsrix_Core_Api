<?php

declare(strict_types=1);

namespace Modules\Audit\Repositories;

use App\RepositoryTrait;
use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Audit\Models\Audit;

/**
 * @property Audit $model
 * @method Audit findOneOrFail($id)
 * @method Audit findOneByOrFail(array $data)
 */
class AuditRepository extends BaseRepository
{
    use RepositoryTrait;
    public function __construct(Audit $model)
    {
        parent::__construct($model);
    }

    public function getAuditList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getAudit(UuidInterface $id): Audit
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createAudit(array $data): Audit
    {
        return $this->create($data);
    }

    public function updateAudit(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteAudit(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

<?php

declare(strict_types=1);

namespace Modules\AdminRequest\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\AdminRequest\Models\AdminRequest;

/**
 * @property AdminRequest $model
 * @method AdminRequest findOneOrFail($id)
 * @method AdminRequest findOneByOrFail(array $data)
 */
class AdminRequestRepository extends BaseRepository
{
    public function __construct(AdminRequest $model)
    {
        parent::__construct($model);
    }

    public function getAdminRequestList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getAdminRequest(UuidInterface $id): AdminRequest
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createAdminRequest(array $data): AdminRequest
    {
        return $this->create($data);
    }

    public function updateAdminRequest(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteAdminRequest(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

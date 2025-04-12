<?php

declare(strict_types=1);

namespace Modules\Shared\Bank\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\Bank\Models\Bank;

/**
 * @property Bank $model
 * @method Bank findOneOrFail($id)
 * @method Bank findOneByOrFail(array $data)
 */
class BankRepository extends BaseRepository
{
    public function __construct(Bank $model)
    {
        parent::__construct($model);
    }

    public function getBankList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getBank(UuidInterface $id): Bank
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createBank(array $data): Bank
    {
        return $this->create($data);
    }

    public function updateBank(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteBank(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

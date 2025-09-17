<?php

declare(strict_types=1);

namespace Modules\Shared\Installment\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\Installment\Models\Installment;
use App\Traits\HasExport;

/**
 * @property Installment $model
 * @method Installment findOneOrFail($id)
 * @method Installment findOneByOrFail(array $data)
 */
class InstallmentRepository extends BaseRepository
{
    use HasExport;

    public function __construct(Installment $model)
    {
        parent::__construct($model);
    }

    public function getInstallmentList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getInstallment(UuidInterface $id): Installment
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createInstallment(array $data): Installment
    {
        return $this->create($data);
    }

    public function updateInstallment(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteInstallment(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBusinessActivity\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoBusinessActivity\Models\EcoBusinessActivity;
use App\Traits\HasExport;

/**
 * @property EcoBusinessActivity $model
 * @method EcoBusinessActivity findOneOrFail($id)
 * @method EcoBusinessActivity findOneByOrFail(array $data)
 */
class EcoBusinessActivityRepository extends BaseRepository
{
    use HasExport;

    public function __construct(EcoBusinessActivity $model)
    {
        parent::__construct($model);
    }

    public function getEcoBusinessActivityList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getEcoBusinessActivity(UuidInterface $companyId): EcoBusinessActivity
    {
        return $this->findOneByOrFail([
            'company_id' => $companyId->toString(),
        ]);
    }

    public function findByCompanyId(UuidInterface $companyId): ?EcoBusinessActivity
    {
        return $this->findOneBy([
            'company_id' => $companyId->toString(),
        ]);
    }

    public function createEcoBusinessActivity(array $data): EcoBusinessActivity
    {
        return $this->create($data);
    }

    public function updateEcoBusinessActivity(UuidInterface $companyId, array $data)
    {
        return $this->updateWhere(['company_id' => $companyId->toString()], $data);
    }

    public function deleteEcoBusinessActivity(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoAppSetting\Models\EcoAppSetting;
use App\Traits\HasExport;

/**
 * @property EcoAppSetting $model
 * @method EcoAppSetting findOneOrFail($id)
 * @method EcoAppSetting findOneByOrFail(array $data)
 */
class EcoAppSettingRepository extends BaseRepository
{
    use HasExport;

    public function __construct(EcoAppSetting $model)
    {
        parent::__construct($model);
    }

    public function getEcoAppSettingList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getEcoAppSetting(UuidInterface $id): EcoAppSetting
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createEcoAppSetting(array $data): EcoAppSetting
    {
        return $this->create($data);
    }

    public function updateEcoAppSetting(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteEcoAppSetting(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function findByCompanyId(string $companyId): ?EcoAppSetting
    {
        return $this->model->where('company_id', $companyId)->first();
    }

    public function upsertByCompanyId(string $companyId, array $data): EcoAppSetting
    {
        return $this->model->updateOrCreate(
            ['company_id' => $companyId],
            $data
        );
    }
}

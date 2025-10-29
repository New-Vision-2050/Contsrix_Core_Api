<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\Banner\Models\SettingPage;
use App\Traits\HasExport;

/**
 * @property SettingPage $model
 * @method SettingPage findOneOrFail($id)
 * @method SettingPage findOneByOrFail(array $data)
 */
class SettingPageRepository extends BaseRepository
{
    use HasExport;

    public function __construct(SettingPage $model)
    {
        parent::__construct($model);
    }

    public function getSettingPageList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getSettingPage(UuidInterface $id): SettingPage
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function findByType( string $type): ?SettingPage
    {
        return $this->model->where('type', $type)
            ->first();
    }

    public function createSettingPage(array $data): SettingPage
    {
        return $this->create($data);
    }

    public function updateSettingPage(UuidInterface $id, array $data): SettingPage
    {
        $settingPage = $this->getSettingPage($id);
        $settingPage->update($data);
        return $settingPage->fresh();
    }

    public function deleteSettingPage(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

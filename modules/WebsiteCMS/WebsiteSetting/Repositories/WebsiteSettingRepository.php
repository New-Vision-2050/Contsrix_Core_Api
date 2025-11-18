<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteSetting\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\WebsiteCMS\WebsiteSetting\Models\WebsiteSetting;
use App\Traits\HasExport;
use Illuminate\Support\Facades\Auth;

/**
 * @property WebsiteSetting $model
 * @method WebsiteSetting findOneOrFail($id)
 * @method WebsiteSetting findOneByOrFail(array $data)
 */
class WebsiteSettingRepository extends BaseRepository
{
    use HasExport;

    public function __construct(WebsiteSetting $model)
    {
        parent::__construct($model);
    }

    public function getWebsiteSettingList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getWebsiteSetting(UuidInterface $id): WebsiteSetting
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createWebsiteSetting(array $data): WebsiteSetting
    {
        // Add company_id to data if not provided
        if (!isset($data['company_id'])) {
            $data['company_id'] = tenant('id');
        }

        return $this->create($data);
    }

    public function updateWebsiteSetting(UuidInterface $id, array $data): WebsiteSetting
    {
        $websiteSetting = $this->getWebsiteSetting($id);
        $this->update($id, $data);
        return $websiteSetting->refresh();
    }

    public function deleteWebsiteSetting(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function getForCurrentCompany(): ?WebsiteSetting
    {
        $companyId = tenant('id');

        return $this->model->newQuery()
            ->where('company_id', $companyId)
            ->first();
    }

    public function updateOrCreateForCurrentCompany(array $data): WebsiteSetting
    {
        $companyId = tenant('id');

        return $this->model->updateOrCreate(
            ['company_id' => $companyId],
            array_merge($data, ['company_id' => $companyId])
        );
    }
}

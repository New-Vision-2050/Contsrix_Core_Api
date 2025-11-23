<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactInfo\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\WebsiteCMS\WebsiteContactInfo\Models\WebsiteContactInfo;
use App\Traits\HasExport;

/**
 * @property WebsiteContactInfo $model
 * @method WebsiteContactInfo findOneOrFail($id)
 * @method WebsiteContactInfo findOneByOrFail(array $data)
 */
class WebsiteContactInfoRepository extends BaseRepository
{
    use HasExport;

    public function __construct(WebsiteContactInfo $model)
    {
        parent::__construct($model);
    }

    public function getWebsiteContactInfoList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getWebsiteContactInfo(UuidInterface $id): WebsiteContactInfo
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createWebsiteContactInfo(array $data): WebsiteContactInfo
    {
        return $this->create($data);
    }

    public function updateWebsiteContactInfo(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteWebsiteContactInfo(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function getCurrentCompanyContactInfo(): ?WebsiteContactInfo
    {
        return $this->model->where('company_id', tenant('id'))->first();
    }

    public function updateCurrentCompanyContactInfo(array $data): WebsiteContactInfo
    {
        $contactInfo = $this->getCurrentCompanyContactInfo();
        
        if (!$contactInfo) {
            $data['company_id'] = tenant('id');
            return $this->create($data);
        }

        $contactInfo->update($data);
        return $contactInfo->fresh();
    }
}

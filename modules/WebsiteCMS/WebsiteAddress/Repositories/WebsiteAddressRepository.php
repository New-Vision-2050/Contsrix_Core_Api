<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAddress\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\WebsiteCMS\WebsiteAddress\Models\WebsiteAddress;
use App\Traits\HasExport;

/**
 * @property WebsiteAddress $model
 * @method WebsiteAddress findOneOrFail($id)
 * @method WebsiteAddress findOneByOrFail(array $data)
 */
class WebsiteAddressRepository extends BaseRepository
{
    use HasExport;

    public function __construct(WebsiteAddress $model)
    {
        parent::__construct($model);
    }

    public function getWebsiteAddressList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getWebsiteAddress(UuidInterface $id): WebsiteAddress
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createWebsiteAddress(array $data): WebsiteAddress
    {
        $data['company_id'] = tenant('id');
        return $this->create($data);
    }

    public function updateWebsiteAddress(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteWebsiteAddress(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

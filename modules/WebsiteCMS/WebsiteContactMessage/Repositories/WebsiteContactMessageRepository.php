<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactMessage\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\WebsiteCMS\WebsiteContactMessage\Models\WebsiteContactMessage;
use App\Traits\HasExport;

/**
 * @property WebsiteContactMessage $model
 * @method WebsiteContactMessage findOneOrFail($id)
 * @method WebsiteContactMessage findOneByOrFail(array $data)
 */
class WebsiteContactMessageRepository extends BaseRepository
{
    use HasExport;

    public function __construct(WebsiteContactMessage $model)
    {
        parent::__construct($model);
    }

    public function getWebsiteContactMessageList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getWebsiteContactMessage(UuidInterface $id): WebsiteContactMessage
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createWebsiteContactMessage(array $data): WebsiteContactMessage
    {
        return $this->create($data);
    }

    public function updateWebsiteContactMessage(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteWebsiteContactMessage(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

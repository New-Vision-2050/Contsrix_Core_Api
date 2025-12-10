<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePage\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\WebsiteCMS\WebsiteHomePage\Models\WebsiteHomePage;
use App\Traits\HasExport;

/**
 * @property WebsiteHomePage $model
 * @method WebsiteHomePage findOneOrFail($id)
 * @method WebsiteHomePage findOneByOrFail(array $data)
 */
class WebsiteHomePageRepository extends BaseRepository
{
    use HasExport;

    public function __construct(WebsiteHomePage $model)
    {
        parent::__construct($model);
    }

    public function getWebsiteHomePageList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getWebsiteHomePage(UuidInterface $id): WebsiteHomePage
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createWebsiteHomePage(array $data): WebsiteHomePage
    {
        return $this->create($data);
    }

    public function updateWebsiteHomePage(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteWebsiteHomePage(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

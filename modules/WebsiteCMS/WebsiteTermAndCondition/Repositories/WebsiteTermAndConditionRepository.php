<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTermAndCondition\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Models\WebsiteTermAndCondition;
use App\Traits\HasExport;

/**
 * @property WebsiteTermAndCondition $model
 * @method WebsiteTermAndCondition findOneOrFail($id)
 * @method WebsiteTermAndCondition findOneByOrFail(array $data)
 */
class WebsiteTermAndConditionRepository extends BaseRepository
{
    use HasExport;

    public function __construct(WebsiteTermAndCondition $model)
    {
        parent::__construct($model);
    }

    public function getWebsiteTermAndConditionList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getWebsiteTermAndCondition(UuidInterface $id): WebsiteTermAndCondition
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createWebsiteTermAndCondition(array $data): WebsiteTermAndCondition
    {
        return $this->create($data);
    }

    public function updateWebsiteTermAndCondition(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteWebsiteTermAndCondition(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

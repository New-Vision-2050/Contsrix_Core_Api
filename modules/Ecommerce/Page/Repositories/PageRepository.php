<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Page\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\Page\Models\Page;
use App\Traits\HasExport;

/**
 * @property Page $model
 * @method Page findOneOrFail($id)
 * @method Page findOneByOrFail(array $data)
 */
class PageRepository extends BaseRepository
{
    use HasExport;

    public function __construct(Page $model)
    {
        parent::__construct($model);
    }

    public function getPageList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getPage(UuidInterface $id): Page
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createPage(array $data): Page
    {
        return $this->create($data);
    }

    public function updatePage(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deletePage(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function getByType(string $type): ?Page
    {
        return $this->model
            ->where('type', $type)
            ->first();
    }

    public function upsertByType(string $type, array $pageData): Page
    {
        return $this->model->updateOrCreate(
            [
                'type' => $type,
                'company_id'=>$pageData['company_id']
            ],
            $pageData
        );
    }
}

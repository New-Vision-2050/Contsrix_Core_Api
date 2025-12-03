<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\CategoryWebsiteCMS\Repositories;

use App\Exceptions\CustomException;
use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Models\CategoryWebsiteCMS;
use App\Traits\HasExport;

/**
 * @property CategoryWebsiteCMS $model
 * @method CategoryWebsiteCMS findOneOrFail($id)
 * @method CategoryWebsiteCMS findOneByOrFail(array $data)
 */
class CategoryWebsiteCMSRepository extends BaseRepository
{
    use HasExport;

    public function __construct(CategoryWebsiteCMS $model)
    {
        parent::__construct($model);
    }

    public function getCategoryWebsiteCMSList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->model->newQuery()
            ->with('typeCategory')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function getCategoryWebsiteCMS(UuidInterface $id): CategoryWebsiteCMS
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createCategoryWebsiteCMS(array $data): CategoryWebsiteCMS
    {
        // Add company_id to data if not provided
        if (!isset($data['company_id'])) {
            $data['company_id'] = tenant('id');
        }

        return $this->create($data);
    }

    public function updateCategoryWebsiteCMS(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteCategoryWebsiteCMS(UuidInterface $id): bool
    {
        $category = $this->find($id);

        if ($category->websiteServices()->count() > 0||$category->websiteIcons()->count() > 0)
        {
            throw new CustomException(__("validation.can-not-delete-has-children"));
        }
        return $this->delete($id);
    }
}

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

        if ($category->websiteServices()->count() > 0||$category->websiteNews()->count() > 0)
        {
            throw new CustomException(__("validation.can-not-delete-has-children"));
        }
        return $this->delete($id);
    }

    public function getAll(): Collection
    {
       return $this->model->query()->filter(request()->all())->get();


    }

    public function paginated(
        array $conditions = [],
        int $page = 1,
        int $perPage = 15,
        string $orderBy = 'created_at',
        string $sortBy = 'desc'
    ): array
    {
        $query = $this->model
            ->newQuery()
            ->leftJoin('translations as t', function ($join) {
                $join->on('category_website_cms.id', '=', 't.translatable_id')
                    ->where('t.locale', app()->getLocale())
                    ->where('t.field', 'name')->where('t.translatable_type', CategoryWebsiteCMS::class);
            });

        if ($orderBy === 'name') {
            $query->orderBy('t.content', $sortBy);
        } else {
            $query->orderBy("category_website_cms.$orderBy", $sortBy);
        }
        $query->filter(request()->all());

        $count = (clone $query)->distinct('category_website_cms.id')->count('category_website_cms.id');

        $paginatedData = $query
            ->select('category_website_cms.*')
            ->forPage($page, $perPage)
            ->get();

        $paginationArray = $this->getPaginationInformation($page, $perPage, $count);

        return [
            'pagination' => $paginationArray['pagination'],
            'data' => $paginatedData,
        ];
    }
}

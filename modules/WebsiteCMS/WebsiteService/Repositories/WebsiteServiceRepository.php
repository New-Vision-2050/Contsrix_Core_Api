<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteService\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\WebsiteCMS\WebsiteService\Models\WebsiteService;

class WebsiteServiceRepository extends BaseRepository
{
    public function __construct(WebsiteService $model)
    {
        parent::__construct($model);
    }

    public function create(array $attributes): WebsiteService
    {
        $attributes['company_id'] = tenant('id');
        return parent::create($attributes);
    }

    public function getWebsiteServiceList(array $filters = [],int $page, int $perPage = 15): array
    {
        $query = $this->model->with(['category', 'previousWorks']);

        if (isset($filters['name'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name->ar', 'like', '%' . $filters['name'] . '%')
                    ->orWhere('name->en', 'like', '%' . $filters['name'] . '%');
            });
        }

        if (isset($filters['reference_number'])) {
            $query->where('reference_number', 'like', '%' . $filters['reference_number'] . '%');
        }

        if (isset($filters['category_website_cms_id'])) {
            $query->where('category_website_cms_id', $filters['category_website_cms_id']);
        }

        $count = $query->count();
        $paginatedData = $query->forPage($page, $perPage)->get();
        $paginationArray = $this->getPaginationInformation($page, $perPage, $count);
        return array_merge($paginationArray, [
            'data' => $paginatedData
        ]);    }

    public function getForExport(array $filters = [])
    {
        $query = $this->model->with(['category', 'previousWorks']);

        if (isset($filters['ids']) && is_array($filters['ids'])) {
            $query->whereIn('id', $filters['ids']);
        }

        if (isset($filters['name'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name->ar', 'like', '%' . $filters['name'] . '%')
                    ->orWhere('name->en', 'like', '%' . $filters['name'] . '%');
            });
        }

        if (isset($filters['reference_number'])) {
            $query->where('reference_number', 'like', '%' . $filters['reference_number'] . '%');
        }

        if (isset($filters['category_website_cms_id'])) {
            $query->where('category_website_cms_id', $filters['category_website_cms_id']);
        }

        return $query->get();
    }
}

<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteService\Repositories;

use App\Exceptions\CustomException;
use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\WebsiteCMS\WebsiteService\Models\WebsiteService;
use ZipStream\Exception;

class WebsiteServiceRepository extends BaseRepository
{
    public function __construct(WebsiteService $model , private FileUploadService $fileUploadService,private PreviousWorkServiceRepository $previousWorkServiceRepository)
    {
        parent::__construct($model);
    }

    public function create(array $attributes): WebsiteService
    {
        $attributes['company_id'] = tenant('id');
        return parent::create($attributes);
    }

    public function createWebsiteService($data , $mainImage=null , $icon=null , $previousWorks=null)
    {
        try {
            DB::beginTransaction();
            $service = $this->create($data);
            // Handle main image
            if ($mainImage) {

                $this->fileUploadService->uploadFile(
                    $service,
                    $mainImage
                    ,
                    'website-service/main-image',
                    'main_image',
                    'public'
                );
            }

            // Handle icon
            if ($icon) {

                $this->fileUploadService->uploadFile(
                    $service,
                    $icon
                    ,
                    'website-service/icon',
                    'icon',
                    'public'
                );
            }

            // Handle previous work
            if ($previousWorks) {
                $this->previousWorkServiceRepository->syncPreviousWork($service->fresh(), $previousWorks);
            }

            DB::commit();

        } catch (Exception $exception) {
            DB::rollBack();
            throw new CustomException($exception->getMessage());
        }
        return $service->load(['category', 'previousWorks']);
    }

    public function updateService($id ,$data , $mainImage=null , $icon=null , $previousWorks=null)
    {
        $this->update($id, $data);
        $service= $this->find($id);


        // Handle main image
        if ($mainImage) {
            $service->clearMediaCollection('main_image');

            $this->fileUploadService->uploadFile(
                $service,
                $mainImage,
                'website-service/main-image',
                'main_image',
                'public'
            );
        }

        // Handle icon
        if ($icon) {
            $service->clearMediaCollection('icon');

            $this->fileUploadService->uploadFile(
                $service,
                $icon,
                'website-service/icon',
                'icon',
                'public'
            );
        }

        // Handle previous work
        if ($previousWorks !== null) {
            $this->previousWorkServiceRepository->syncPreviousWork($service, $previousWorks);
        }

        return $service->load(['category', 'previousWorks']);
    }


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

    public function updateStatus(string $id, int $status): WebsiteService
    {
        $websiteService = $this->find($id);
        $websiteService->update(['status' => $status]);
        return $websiteService->fresh();
    }

    public function getCurrentCompanyWebsiteServices(?int $limit = null)
    {
        $query = $this->model
            ->with(['category', 'previousWorks'])
            ->where('company_id', tenant('id'))
            ->where('status', 1);

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }
}

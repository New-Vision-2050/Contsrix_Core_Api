<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoCategory\Models\EcoCategory;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\Company\CompanyCore\Models\Company;

/**
 * @property EcoCategory $model
 * @method EcoCategory findOneOrFail($id)
 * @method EcoCategory findOneByOrFail(array $data)
 */
class EcoCategoryRepository extends BaseRepository
{
    public function __construct(
        EcoCategory $model,
        private FileUploadService $fileUploadService
    ) {
        parent::__construct($model);
    }

    public function getEcoCategoryList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }
    public function paginated(
        array $conditions = [],
        int $page = 1,
        int $perPage = 15,
        string $orderBy = 'created_at',
        string $sortBy = 'desc',
        array $relations = [] 
    ) {
        if (method_exists($this->model, 'scopeFilter')) {
            $query = $this->model->filter(request()->all())->where($conditions);
        } else {
            $query = $this->model->where($conditions);
        }

        if (!empty($relations)) {
            $query->with($relations);
        }

        $count = $query->count();

        $paginatedData = $query
            ->orderBy($orderBy, $sortBy)
            ->forPage($page, $perPage)
            ->get();

        $paginationArray = $this->getPaginationInformation($page, $perPage, $count);

        return [
            'pagination' => $paginationArray['pagination'],
            'data' => $paginatedData,
        ];
    }

    public function getEcoCategory(UuidInterface $id, array $relations = []): EcoCategory
    {
        $category = $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);

        if (!empty($relations)) {
            $category->load($relations);
        }

        return $category;
    }

    public function createEcoCategory(array $data, $file = null): EcoCategory
    {
        $category = $this->create($data);
        
        if ($file) {
            $category->clearMediaCollection('upload');
            $companyName = Company::find($data['company_id'])?->name ?? 'UnknownCompany';
            $path = $companyName . '/categories';

            $this->fileUploadService->uploadFile(
                $category,
                $file,
                $path,
                'upload',
                "public"
            );
        }

        return $category;
    }

    public function updateEcoCategory(UuidInterface $id, array $data, $file = null): bool
    {
        $updated = $this->update($id, $data);
        
        if ($file && $updated) {
            $category = $this->findOneOrFail($id);
            $category->clearMediaCollection('upload');
            $companyName = Company::find($data['company_id'] ?? $category->company_id)?->name ?? 'UnknownCompany';
            $path = $companyName . '/categories';

            $this->fileUploadService->uploadFile(
                $category,
                $file,
                $path,
                'upload',
                "public"
            );
        }

        return $updated;
    }

    public function deleteEcoCategory(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

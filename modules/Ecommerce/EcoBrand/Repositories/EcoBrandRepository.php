<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoBrand\Models\EcoBrand;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\Company\CompanyCore\Models\Company;

/**
 * @property EcoBrand $model
 * @method EcoBrand findOneOrFail($id)
 * @method EcoBrand findOneByOrFail(array $data)
 */
class EcoBrandRepository extends BaseRepository
{
    public function __construct(
        EcoBrand $model,
        private FileUploadService $fileUploadService
    ) {
        parent::__construct($model);
    }

    public function getEcoBrandList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getEcoBrand(UuidInterface $id): EcoBrand
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createEcoBrand(array $data, $file = null): EcoBrand
    {
        $brand = $this->create($data);
        
        if ($file) {
            $brand->clearMediaCollection('upload');
            $companyName = Company::find($data['company_id'])?->name ?? 'UnknownCompany';
            $path = $companyName . '/brands';

            $this->fileUploadService->uploadFile(
                $brand,
                $file,
                $path,
                'upload',
                "public"
            );
        }

        return $brand;
    }

    public function updateEcoBrand(UuidInterface $id, array $data, $file = null): bool
    {
        $updated = $this->update($id, $data);
        
        if ($file && $updated) {
            $brand = $this->findOneOrFail($id);
            $brand->clearMediaCollection('upload');
            $companyName = Company::find($data['company_id'] ?? $brand->company_id)?->name ?? 'UnknownCompany';
            $path = $companyName . '/brands';

            $this->fileUploadService->uploadFile(
                $brand,
                $file,
                $path,
                'upload',
                "public"
            );
        }

        return $updated;
    }

    public function deleteEcoBrand(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

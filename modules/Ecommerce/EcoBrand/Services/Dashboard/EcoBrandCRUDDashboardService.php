<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\Services\Dashboard;

use Modules\Ecommerce\EcoBrand\DTO\Dashboard\CreateEcoBrandDashboardDTO;
use Modules\Ecommerce\EcoBrand\Models\EcoBrand;
use Modules\Ecommerce\EcoBrand\Repositories\EcoBrandRepository;
use Ramsey\Uuid\UuidInterface;

class EcoBrandCRUDDashboardService
{
    public function __construct(
        private EcoBrandRepository $repository,
    ) {
    }

    public function create(CreateEcoBrandDashboardDTO $createEcoBrandDTO, $file = null): EcoBrand
    {
         return $this->repository->createEcoBrand($createEcoBrandDTO->toArray(), $file);
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): EcoBrand
    {
        return $this->repository->getEcoBrand(
            id: $id,
        );
    }

    /**
     * Toggle brand active status
     */
    public function toggleActive(UuidInterface $id): array
    {
        $brand = $this->get($id);
        
        // Toggle the is_active status
        $newStatus = !$brand->is_active;
        $brand->update(['is_active' => $newStatus]);

        $statusText = $newStatus ? 'نشط' : 'غير مفعل';
        
        return [
            'message' => "تم تغيير حالة العلامة التجارية إلى: {$statusText}",
            'is_active' => $newStatus,
            'status_text' => $statusText,
            'brand' => $brand
        ];
    }
}

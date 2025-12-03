<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FeatureDeal\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\FeatureDeal\DTO\CreateFeatureDealDTO;
use Modules\Ecommerce\FeatureDeal\DTO\UpdateFeatureDealDTO;
use Modules\Ecommerce\FeatureDeal\Models\FeatureDeal;
use Modules\Ecommerce\FeatureDeal\Repositories\FeatureDealRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class FeatureDealCRUDService
{
    use HasExportService;

    public function __construct(
        private FeatureDealRepository $repository,
    ) {
    }

    public function create(CreateFeatureDealDTO $createFeatureDealDTO): FeatureDeal
    {
         return $this->repository->createFeatureDeal(
             data: $createFeatureDealDTO->toArray(),
             productIds: $createFeatureDealDTO->products(),
         );
    }

    public function update(UuidInterface $id, UpdateFeatureDealDTO $updateFeatureDealDTO): FeatureDeal
    {
        return $this->repository->updateFeatureDeal(
            id: $id,
            data: $updateFeatureDealDTO->toArray(),
            productIds: $updateFeatureDealDTO->products(),
        );
    }

    public function delete(UuidInterface $id): bool
    {
        return $this->repository->deleteFeatureDeal($id);
    }

    public function list(int $page = 1, int $perPage = 10, array $relations = ['company', 'products']): array
    {
        return $this->repository->paginatedWithRelations(
            page: $page,
            perPage: $perPage,
            relations: $relations
        );
    }

    public function get(UuidInterface $id): FeatureDeal
    {
        return $this->repository->getFeatureDeal($id);
    }

    public function getWithRelations(UuidInterface $id): FeatureDeal
    {
        return $this->repository->getFeatureDealWithRelations($id);
    }

    /**
     * Toggle feature deal active status
     */
    public function toggleStatus(UuidInterface $id): array
    {
        $featureDeal = $this->get($id);
        
        // Toggle the is_active status
        $newStatus = !$featureDeal->is_active;
        $this->repository->updateFeatureDeal($id, ['is_active' => $newStatus]);

        // Refresh the model to get updated data
        $featureDeal = $this->get($id);

        $statusText = $newStatus ? 'نشط' : 'غير مفعل';
        
        return [
            'message' => "تم تغيير حالة العرض المميز إلى: {$statusText}",
            'is_active' => $newStatus,
            'status_text' => $statusText,
            'feature_deal' => $featureDeal
        ];
    }

    /**
     * Get feature deal statistics for dashboard
     */
    public function getFeatureDealStatistics(): array
    {
        // Get total feature deals count
        $totalFeatureDeals = FeatureDeal::count();

        // Get active feature deals count
        $activeFeatureDeals = FeatureDeal::where('is_active', 1)->count();

        // Get inactive feature deals count
        $inactiveFeatureDeals = FeatureDeal::where('is_active', 0)->count();

        // Get current feature deals (within date range and active)
        $currentFeatureDeals = FeatureDeal::current()->active()->count();

        return [
            [
                'number' => $totalFeatureDeals,
                'title' => 'اجمالي عدد العروض المميزة',
            ],
            [
                'number' => $activeFeatureDeals,
                'title' => 'العروض المميزة النشطة',
            ],
            [
                'number' => $currentFeatureDeals,
                'title' => 'العروض المميزة الحالية',
            ],
            [
                'number' => $inactiveFeatureDeals,
                'title' => 'العروض المميزة الغير فعالة',
            ]
        ];
    }
}

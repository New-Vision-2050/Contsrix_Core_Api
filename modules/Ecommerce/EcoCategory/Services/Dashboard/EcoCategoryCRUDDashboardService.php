<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Services\Dashboard;

use Modules\Ecommerce\EcoCategory\DTO\Dashboard\CreateEcoCategoryDashboardDTO;
use Modules\Ecommerce\EcoCategory\Models\EcoCategory;
use Modules\Ecommerce\EcoCategory\Repositories\EcoCategoryRepository;
use Ramsey\Uuid\UuidInterface;

class EcoCategoryCRUDDashboardService
{
    public function __construct(
        private EcoCategoryRepository $repository,
    ) {
    }

    public function create(CreateEcoCategoryDashboardDTO $createEcoCategoryDTO, $file = null): EcoCategory
    {
         return $this->repository->createEcoCategory($createEcoCategoryDTO->toArray(), $file);
    }

    public function list(int $page = 1, int $perPage = 10, array $relations = []): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
            relations: $relations
        );
    }

    public function get(UuidInterface $id): EcoCategory
    {
        return $this->repository->getEcoCategory(
            id: $id,
        );
    }

    /**
     * Toggle category active status
     */
    public function toggleActive(UuidInterface $id): array
    {
        $category = $this->get($id);
        
        // Toggle the is_active status
        $newStatus = !$category->is_active;
        $category->update(['is_active' => $newStatus]);

        $statusText = $newStatus ? 'نشط' : 'غير مفعل';
        
        return [
            'message' => "تم تغيير حالة التصنيف إلى: {$statusText}",
            'is_active' => $newStatus,
            'status_text' => $statusText,
            'category' => $category
        ];
    }

    /**
     * Get category statistics for dashboard cards
     */
    public function getCategoryStatistics(): array
    {
            // Get main categories (level 1 - no parent)
            $mainCategories = EcoCategory::whereNull('parent_id')->count();

            // Get subcategories (level 2 - have parent but parent has no parent)
            $subcategories = EcoCategory::whereHas('parent', function($query) {
                $query->whereNull('parent_id');
            })->count();

            // Get sub-subcategories (level 3 - have parent whose parent also has parent)
            $subSubcategories = EcoCategory::whereHas('parent.parent')->count();

            return [
                [
                    'number' => $mainCategories,
                    'title' => 'اجمالي عدد الاقسام',
                ],
                [
                    'number' => $subcategories,
                    'title' =>'اجمالي عدد الاقسام  الفرعية',
                ],
                [
                    'number' => $subSubcategories,
                    'title' => 'اجمالي عدد الاقسام الفرعية الفرعية',
                ]
            ];

     
    }
}

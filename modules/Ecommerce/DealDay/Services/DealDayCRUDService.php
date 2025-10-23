<?php

declare(strict_types=1);

namespace Modules\Ecommerce\DealDay\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\DealDay\DTO\CreateDealDayDTO;
use Modules\Ecommerce\DealDay\DTO\UpdateDealDayDTO;
use Modules\Ecommerce\DealDay\Models\DealDay;
use Modules\Ecommerce\DealDay\Repositories\DealDayRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class DealDayCRUDService
{
    use HasExportService;

    public function __construct(
        private DealDayRepository $repository,
    ) {
    }

    public function create(CreateDealDayDTO $createDealDayDTO): DealDay
    {
         return $this->repository->createDealDay($createDealDayDTO->toArray());
    }

    public function update(UuidInterface $id, UpdateDealDayDTO $updateDealDayDTO): DealDay
    {
        return $this->repository->updateDealDay($id, $updateDealDayDTO->toArray());
    }

    public function delete(UuidInterface $id): bool
    {
        return $this->repository->deleteDealDay($id);
    }

    public function list(int $page = 1, int $perPage = 10, array $relations = []): array
    {
        return $this->repository->paginatedWithRelations(
            page: $page,
            perPage: $perPage,
            relations: $relations
        );
    }

    public function get(UuidInterface $id): DealDay
    {
        return $this->repository->getDealDay($id);
    }

    public function getWithRelations(UuidInterface $id): DealDay
    {
        return $this->repository->getDealDayWithRelations($id);
    }

    /**
     * Toggle deal day active status
     */
    public function toggleStatus(UuidInterface $id): array
    {
        $dealDay = $this->get($id);
        
        // Toggle the is_active status
        $newStatus = !$dealDay->is_active;
        $this->repository->updateDealDay($id, ['is_active' => $newStatus]);

        // Refresh the model to get updated data
        $dealDay = $this->get($id);

        $statusText = $newStatus ? 'نشط' : 'غير مفعل';
        
        return [
            'message' => "تم تغيير حالة العرض إلى: {$statusText}",
            'is_active' => $newStatus,
            'status_text' => $statusText,
            'deal_day' => $dealDay
        ];
    }

    /**
     * Get deal day statistics for dashboard
     */
    public function getDealDayStatistics(): array
    {
        // Get total deal days count
        $totalDealDays = DealDay::count();

        // Get active deal days count
        $activeDealDays = DealDay::where('is_active', 1)->count();

        // Get inactive deal days count
        $inactiveDealDays = DealDay::where('is_active', 0)->count();

        return [
            [
                'number' => $totalDealDays,
                'title' => 'اجمالي عدد العروض',
            ],
            [
                'number' => $activeDealDays,
                'title' => 'العروض النشطة',
            ],
            [
                'number' => $inactiveDealDays,
                'title' => 'العروض الغير فعالة',
            ]
        ];
    }
}

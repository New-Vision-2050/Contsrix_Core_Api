<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FlashDeal\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\FlashDeal\DTO\CreateFlashDealDTO;
use Modules\Ecommerce\FlashDeal\Models\FlashDeal;
use Modules\Ecommerce\FlashDeal\Repositories\FlashDealRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

class FlashDealCRUDService
{
    use HasExportService;

    public function __construct(
        private FlashDealRepository $repository,
    ) {
    }

    public function create(CreateFlashDealDTO $createFlashDealDTO, ?UploadedFile $image = null): FlashDeal
    {
        // Business logic: Validate date ranges
        $this->validateDateRange($createFlashDealDTO->startDate, $createFlashDealDTO->endDate);

        // Create the flash deal
        $flashDeal = $this->repository->createFlashDeal(
            data: $createFlashDealDTO->toArray(),
            productIds: $createFlashDealDTO->products(),
        );

        // Handle media upload if provided
        if ($image) {
            $this->attachMedia($flashDeal, $image);
        }

        return $flashDeal;
    }

    public function update(UuidInterface $id, array $data, ?UploadedFile $image = null, ?array $productIds = null): FlashDeal
    {
        $flashDeal = $this->repository->updateFlashDeal($id, $data, $productIds);

        // Handle media upload if provided
        if ($image) {
            $this->replaceMedia($flashDeal, $image);
        }

        return $flashDeal;
    }

    public function toggleStatus(UuidInterface $id): FlashDeal
    {
        $flashDeal = $this->repository->getFlashDeal($id);
        
        // Toggle the is_active status
        $newStatus = !$flashDeal->is_active;
        
        return $this->repository->updateFlashDeal($id, ['is_active' => $newStatus]);
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginatedWithRelations(
            page: $page,
            perPage: $perPage,
            relations: ['company', 'products'],
        );
    }

    public function get(UuidInterface $id): FlashDeal
    {
        return $this->repository->getFlashDeal(
            id: $id,
        );
    }

    /**
     * Get currently active flash deals
     */
    public function getCurrentlyActiveDeals(): Collection
    {
        return $this->repository->getCurrentlyActiveDeals();
    }

    /**
     * Get upcoming flash deals
     */
    public function getUpcomingDeals(): Collection
    {
        return $this->repository->getUpcomingDeals();
    }

    /**
     * Get expired flash deals
     */
    public function getExpiredDeals(): Collection
    {
        return $this->repository->getExpiredDeals();
    }

    /**
     * Automatically deactivate expired deals
     */
    public function deactivateExpiredDeals(): int
    {
        return $this->repository->deactivateExpiredDeals();
    }

    /**
     * Get flash deals by company
     */
    public function getByCompany(string $companyId): Collection
    {
        return $this->repository->getByCompany($companyId);
    }

    /**
     * Validate date range business rules
     */
    private function validateDateRange(string $startDate, string $endDate): void
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        if ($start->gte($end)) {
            throw new \InvalidArgumentException('تاريخ انتهاء العرض يجب أن يكون بعد تاريخ البداية');
        }
        
        if ($start->lt(now())) {
            throw new \InvalidArgumentException('تاريخ بداية العرض يجب أن يكون في المستقبل');
        }
        
        // Business rule: Maximum deal duration is 30 days
        if ($start->diffInDays($end) > 30) {
            throw new \InvalidArgumentException('مدة العرض يجب ألا تتجاوز 30 يوم');
        }
    }



    /**
     * Attach media to flash deal
     */
    private function attachMedia(FlashDeal $flashDeal, UploadedFile $image): void
    {
        try {
            $flashDeal->addMedia($image)
                ->toMediaCollection('upload');
        } catch (\Exception $e) {
            throw new \RuntimeException('فشل في رفع الصورة: ' . $e->getMessage());
        }
    }

    /**
     * Replace existing media with new one
     */
    private function replaceMedia(FlashDeal $flashDeal, UploadedFile $image): void
    {
        try {
            // Clear existing media
            $flashDeal->clearMediaCollection('upload');
            
            // Add new media
            $flashDeal->addMedia($image)
                ->toMediaCollection('upload');
        } catch (\Exception $e) {
            throw new \RuntimeException('فشل في استبدال الصورة: ' . $e->getMessage());
        }
    }

    /**
     * Search flash deals with filters
     */
    public function search(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        return $this->repository->searchFlashDeals($filters, $page, $perPage);
    }

    /**
     * Get flash deals for export with filters
     */
    public function getForExport(array $filters = [])
    {
        return $this->repository->getForExport($filters);
    }
}

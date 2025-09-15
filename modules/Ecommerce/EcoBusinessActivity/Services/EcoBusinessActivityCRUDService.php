<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBusinessActivity\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\EcoBusinessActivity\DTO\CreateEcoBusinessActivityDTO;
use Modules\Ecommerce\EcoBusinessActivity\Models\EcoBusinessActivity;
use Modules\Ecommerce\EcoBusinessActivity\Repositories\EcoBusinessActivityRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class EcoBusinessActivityCRUDService
{
    use HasExportService;

    public function __construct(
        private EcoBusinessActivityRepository $repository,
    ) {
    }

    public function create(CreateEcoBusinessActivityDTO $createEcoBusinessActivityDTO): EcoBusinessActivity
    {
         return $this->repository->createEcoBusinessActivity($createEcoBusinessActivityDTO->toArray());
    }

    public function upsert(CreateEcoBusinessActivityDTO $createEcoBusinessActivityDTO): EcoBusinessActivity
    {
        // Check if business activity already exists for this company
        $existingActivity = $this->repository->findByCompanyId($createEcoBusinessActivityDTO->getCompanyId());
        
        if ($existingActivity) {
            // Update existing business activity
            $this->repository->updateEcoBusinessActivity($createEcoBusinessActivityDTO->getCompanyId(), $createEcoBusinessActivityDTO->toArray());
            return $this->repository->getEcoBusinessActivity($createEcoBusinessActivityDTO->getCompanyId());
        } else {
            // Create new business activity
            return $this->repository->createEcoBusinessActivity($createEcoBusinessActivityDTO->toArray());
        }
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $companyId): EcoBusinessActivity
    {
        return $this->repository->getEcoBusinessActivity(
            companyId: $companyId,
        );
    }
}

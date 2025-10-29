<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Services;

use Modules\Ecommerce\Banner\DTO\UpsertSettingPageDTO;
use Modules\Ecommerce\Banner\Models\SettingPage;
use Modules\Ecommerce\Banner\Repositories\SettingPageRepository;
use Ramsey\Uuid\UuidInterface;
use Ramsey\Uuid\Uuid;
use App\Traits\HasExportService;

class SettingPageCRUDService
{
    use HasExportService;

    public function __construct(
        private SettingPageRepository $repository,
    ) {
    }

    public function upsert(UpsertSettingPageDTO $upsertSettingPageDTO): SettingPage
    {
        // Check if setting page exists for this company and type
        $existingSettingPage = $this->repository->findByType(
            $upsertSettingPageDTO->type
        );

        if ($existingSettingPage) {
            // Update existing setting page
            return $this->repository->updateSettingPage(Uuid::fromString($existingSettingPage->id), $upsertSettingPageDTO->toArray());
        }

        // Create new setting page
        return $this->repository->createSettingPage($upsertSettingPageDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): SettingPage
    {
        return $this->repository->getSettingPage($id);
    }

    public function getByCompanyAndType(UuidInterface $companyId, string $type): ?SettingPage
    {
        return $this->repository->findByType($type);
    }

    public function getByType(string $type): ?SettingPage
    {
        return $this->repository->findByType( $type);
    }

    public function delete(UuidInterface $id): bool
    {
        return $this->repository->deleteSettingPage($id);
    }

    public function toggleStatus(UuidInterface $id): SettingPage
    {
        $settingPage = $this->repository->getSettingPage($id);
        
        // Toggle the is_active status
        $newStatus = !$settingPage->is_active;
        
        return $this->repository->updateSettingPage($id, ['is_active' => $newStatus]);
    }
}

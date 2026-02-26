<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\Services;

use Illuminate\Support\Collection;
use Modules\Project\TermSetting\DTO\CreateTermSettingDTO;
use Modules\Project\TermSetting\Models\TermSetting;
use Modules\Project\TermSetting\Repositories\TermSettingRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class TermSettingCRUDService
{
    use HasExportService;

    public function __construct(
        private TermSettingRepository $repository,
    ) {
    }

    public function create(CreateTermSettingDTO $createTermSettingDTO): TermSetting
    {
         return $this->repository->createTermSetting(
             $createTermSettingDTO->toArray(),
             $createTermSettingDTO->getTermServicesIds()
         );
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            conditions: ["parent_id"=>null],
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(int $id): TermSetting
    {
        return $this->repository->getTermSetting($id);
    }

    public function getWithRelations(int $id): TermSetting
    {
        return $this->repository->getTermSettingWithRelations($id);
    }

    public function getWithChildren(int $id): TermSetting
    {
        return $this->repository->getTermSettingWithChildren($id);
    }

    public function getChildren(int $id): Collection
    {
        return $this->repository->getTermSettingChildren($id);
    }
}

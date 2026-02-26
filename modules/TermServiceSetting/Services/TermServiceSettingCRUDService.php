<?php

declare(strict_types=1);

namespace Modules\TermServiceSetting\Services;

use Illuminate\Support\Collection;
use Modules\TermServiceSetting\DTO\CreateTermServiceSettingDTO;
use Modules\TermServiceSetting\Models\TermServiceSetting;
use Modules\TermServiceSetting\Repositories\TermServiceSettingRepository;
use App\Traits\HasExportService;

class TermServiceSettingCRUDService
{
    use HasExportService;

    public function __construct(
        private TermServiceSettingRepository $repository,
    ) {
    }

    public function create(CreateTermServiceSettingDTO $createTermServiceSettingDTO): TermServiceSetting
    {
         return $this->repository->createTermServiceSetting(
             $createTermServiceSettingDTO->toArray(),
             $createTermServiceSettingDTO->getTermSettingIds()
         );
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(int $id): TermServiceSetting
    {
        return $this->repository->getTermServiceSetting(
            id: $id,
        );
    }

    public function getAll(): Collection
    {
        return $this->repository->getAllTermServiceSettings();
    }
}

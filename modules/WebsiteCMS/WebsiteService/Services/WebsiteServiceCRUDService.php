<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteService\Services;

use App\Exceptions\CustomException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\WebsiteCMS\WebsiteService\Commands\UpdateWebsiteServiceCommand;
use Modules\WebsiteCMS\WebsiteService\DTO\CreateWebsiteServiceDTO;
use Modules\WebsiteCMS\WebsiteService\Models\PreviousWork;
use Modules\WebsiteCMS\WebsiteService\Models\WebsiteService;
use Modules\WebsiteCMS\WebsiteService\Repositories\WebsiteServiceRepository;
use Modules\WebsiteCMS\WebsiteHomePage\Services\WebsiteHomePageService;
use ZipStream\Exception;

class WebsiteServiceCRUDService
{
    public function __construct(
        private WebsiteServiceRepository $repository,
        private WebsiteHomePageService $homePageService,
    )
    {
    }

    public function create(CreateWebsiteServiceDTO $dto)
    {
       $service = $this->repository->createWebsiteService($dto->toArray(), $dto->getMainImage(), $dto->getIcon(), $dto->getPreviousWork());
       
       $this->homePageService->clearCache();
       
       return $service;
    }

    public function update(UpdateWebsiteServiceCommand $command): WebsiteService
    {
       $service = $this->repository->updateService($command->getId(), $command->toArray(), $command->getMainImage(), $command->getIcon(), $command->getPreviousWork());
       
       $this->homePageService->clearCache();
       
       return $service;
    }

    public function list(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        return $this->repository->paginated( [],$page, $perPage);
    }

    public function get(string $id): ?WebsiteService
    {
        return $this->repository->find($id, ['category', 'previousWorks']);
    }

    public function delete(string $id): bool
    {
        $result = $this->repository->delete($id);
        
        $this->homePageService->clearCache();
        
        return $result;
    }

    public function getForExport(array $filters = [])
    {
        return $this->repository->getForExport($filters);
    }

    public function updateStatus(string $id, int $status): WebsiteService
    {
        $service = $this->repository->updateStatus($id, $status);
        
        $this->homePageService->clearCache();
        
        return $service;
    }


}

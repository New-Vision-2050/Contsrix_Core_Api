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
use ZipStream\Exception;

class WebsiteServiceCRUDService
{
    public function __construct(
        private WebsiteServiceRepository $repository,
        private FileUploadService        $fileUploadService
    )
    {
    }

    public function create(CreateWebsiteServiceDTO $dto)
    {

       $this->repository->createWebsiteService($dto->toArray(), $dto->getMainImage(), $dto->getIcon(), $dto->getPreviousWork());


    }

    public function update(UpdateWebsiteServiceCommand $command): WebsiteService
    {



        $this->repository->update($command->getId(), $command->toArray());
        $service= $this->repository->find($command->getId());


        // Handle main image
        if ($command->getMainImage()) {
            $service->clearMediaCollection('main_image');

            $this->fileUploadService->uploadFile(
                $service,
                $command->getMainImage(),
                'website-service/main-image',
                'main_image',
                'public'
            );
        }

        // Handle icon
        if ($command->getIcon()) {
            $service->clearMediaCollection('icon');

            $this->fileUploadService->uploadFile(
                $service,
                $command->getIcon(),
                'website-service/icon',
                'icon',
                'public'
            );
        }

        // Handle previous work
        if ($command->getPreviousWork() !== null) {
            $this->syncPreviousWork($service, $command->getPreviousWork());
        }

        return $service->load(['category', 'previousWorks']);
    }

    public function list(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        return $this->repository->getWebsiteServiceList($filters, $page, $perPage);
    }

    public function get(string $id): ?WebsiteService
    {
        return $this->repository->find($id, ['category', 'previousWorks']);
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }

    public function getForExport(array $filters = [])
    {
        return $this->repository->getForExport($filters);
    }

    public function updateStatus(string $id, int $status): WebsiteService
    {
        return $this->repository->updateStatus($id, $status);
    }


}

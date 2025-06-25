<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Modules\Services;

use Illuminate\Support\Collection;
use Modules\SubscriptionSystem\Modules\DTO\CreateModulesDTO;
use Modules\SubscriptionSystem\Modules\Models\Module;
use Modules\SubscriptionSystem\Modules\Repositories\ModulesRepository;
use Ramsey\Uuid\UuidInterface;

class ModulesCRUDService
{
    public function __construct(
        private ModulesRepository $repository,
    ) {
    }

    public function create(CreateModulesDTO $createModulesDTO): Module
    {
         return $this->repository->createModules($createModulesDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        $query = $this->repository->query()
            ->with(['features', 'children.features']) 
            ->whereNull('parent_id');
    
        $count = $query->count();
        $paginatedData = $query->forPage($page, $perPage)->get();
        $paginationArray = $this->repository->getPaginationInformation($page, $perPage, $count);
    
        return [
            'pagination' => $paginationArray['pagination'],
            'data' => $paginatedData,
        ];
    }

    public function get(UuidInterface $id): Module
    {
        return $this->repository->getModules(
            id: $id,
        );
    }
}

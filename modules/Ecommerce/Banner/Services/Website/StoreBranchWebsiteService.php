<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Services\Website;

use Modules\Ecommerce\Banner\Models\StoreBranch;
use Modules\Ecommerce\Banner\Repositories\StoreBranchRepository;
use Ramsey\Uuid\UuidInterface;

class StoreBranchWebsiteService
{
    public function __construct(
        private StoreBranchRepository $repository,
    ) {
    }

    public function list(int $page = 1, int $perPage = 10, ?string $type = null): array
    {
        $filters = ['is_active' => true];
        
        if ($type) {
            $filters['type'] = $type;
        }

        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
            filters: $filters
        );
    }

    public function get(UuidInterface $id): StoreBranch
    {
        $storeBranch = $this->repository->getStoreBranch($id);
        
        if (!$storeBranch->is_active) {
            abort(404, 'Store branch not found');
        }
        
        return $storeBranch;
    }
}


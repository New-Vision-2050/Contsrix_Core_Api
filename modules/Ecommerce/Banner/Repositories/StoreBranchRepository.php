<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Repositories;

use Modules\Ecommerce\Banner\Models\StoreBranch;
use Ramsey\Uuid\UuidInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class StoreBranchRepository
{
    public function __construct(
        private StoreBranch $model
    ) {
    }

    public function createStoreBranch(array $data): StoreBranch
    {
        return $this->model->create($data);
    }

    public function getStoreBranch(UuidInterface $id): StoreBranch
    {
        return $this->model->with(['company', 'country'])->findOrFail($id->toString());
    }

    public function updateStoreBranch(UuidInterface $id, array $data): StoreBranch
    {
        $storeBranch = $this->getStoreBranch($id);
        $storeBranch->update($data);
        
        return $storeBranch->fresh(['company', 'country']);
    }

    public function deleteStoreBranch(UuidInterface $id): bool
    {
        $storeBranch = $this->getStoreBranch($id);
        
        return $storeBranch->delete();
    }

    public function toggleStatus(UuidInterface $id): StoreBranch
    {
        $storeBranch = $this->getStoreBranch($id);
        $newStatus = !$storeBranch->is_active;
        
        return $this->updateStoreBranch($id, ['is_active' => $newStatus]);
    }

    public function paginated(int $page = 1, int $perPage = 10): array
    {
        $query = $this->model->query()
            ->with(['company', 'country'])
            ->orderBy('created_at', 'desc');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }

    public function getByType(string $type): array
    {
        return $this->model->byType($type)
            ->active()
            ->with(['company', 'country'])
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function getByCountry(UuidInterface $countryId): array
    {
        return $this->model->byCountry($countryId->toString())
            ->active()
            ->with(['company', 'country'])
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function getActiveStoreBranches(): array
    {
        return $this->model->active()
            ->with(['company', 'country'])
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function searchByName(string $name): array
    {
        return $this->model->where('name', 'like', '%' . $name . '%')
            ->active()
            ->with(['company', 'country'])
            ->orderBy('name')
            ->get()
            ->toArray();
    }
}

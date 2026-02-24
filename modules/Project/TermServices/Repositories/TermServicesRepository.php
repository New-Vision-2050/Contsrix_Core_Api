<?php

declare(strict_types=1);

namespace Modules\Project\TermServices\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Project\TermServices\Models\TermServices;
use App\Traits\HasExport;

/**
 * @property TermServices $model
 * @method TermServices findOneOrFail($id)
 * @method TermServices findOneByOrFail(array $data)
 */
class TermServicesRepository extends BaseRepository
{
    use HasExport;

    public function __construct(TermServices $model)
    {
        parent::__construct($model);
    }

    public function getTermServicesList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getTermServices(UuidInterface $id): TermServices
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createTermServices(array $data): TermServices
    {
        return $this->create($data);
    }

    public function updateTermServices(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteTermServices(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

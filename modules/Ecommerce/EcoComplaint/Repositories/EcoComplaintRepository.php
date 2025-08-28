<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoComplaint\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoComplaint\Models\EcoComplaint;
use App\Traits\HasExport;

/**
 * @property EcoComplaint $model
 * @method EcoComplaint findOneOrFail($id)
 * @method EcoComplaint findOneByOrFail(array $data)
 */
class EcoComplaintRepository extends BaseRepository
{
    use HasExport;

    public function __construct(EcoComplaint $model)
    {
        parent::__construct($model);
    }

    public function getEcoComplaintList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getEcoComplaint(UuidInterface $id): EcoComplaint
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createEcoComplaint(array $data): EcoComplaint
    {
        return $this->create($data);
    }

    public function updateEcoComplaint(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteEcoComplaint(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

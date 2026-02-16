<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\MedicalInsurance\Models\MedicalInsurance;
use App\Traits\HasExport;

/**
 * @property MedicalInsurance $model
 * @method MedicalInsurance findOneOrFail($id)
 * @method MedicalInsurance findOneByOrFail(array $data)
 */
class MedicalInsuranceRepository extends BaseRepository
{
    use HasExport;

    public function __construct(MedicalInsurance $model)
    {
        parent::__construct($model);
    }

    public function getMedicalInsuranceList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList(['employee'], $page, $perPage);
    }

    public function getMedicalInsurance(UuidInterface $id): MedicalInsurance
    {
        return $this->model->with('employee')->where('id', $id->toString())->firstOrFail();
    }

    public function createMedicalInsurance(array $data): MedicalInsurance
    {
        return $this->create($data);
    }

    public function updateMedicalInsurance(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteMedicalInsurance(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

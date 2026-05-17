<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Ramsey\Uuid\UuidInterface;
use Modules\MedicalInsurance\Models\MedicalInsuranceCategory;

/**
 * @property MedicalInsuranceCategory $model
 * @method MedicalInsuranceCategory findOneOrFail($id)
 */
class MedicalInsuranceCategoryRepository extends BaseRepository
{
    public function __construct(MedicalInsuranceCategory $model)
    {
        parent::__construct($model);
    }

    public function listByInsurance(string $medicalInsuranceId, int $page = 1, int $perPage = 10): array
    {
        $query = $this->model->where('medical_insurance_id', $medicalInsuranceId);
        $total = $query->count();

        $data = $query->orderBy('created_at', 'desc')
            ->forPage($page, $perPage)
            ->get();

        return [
            'data' => $data,
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage),
            ],
        ];
    }

    public function getCategory(UuidInterface $id): MedicalInsuranceCategory
    {
        return $this->model->where('id', $id->toString())->firstOrFail();
    }

    public function createCategory(array $data): MedicalInsuranceCategory
    {
        return $this->create($data);
    }

    public function updateCategory(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteCategory(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

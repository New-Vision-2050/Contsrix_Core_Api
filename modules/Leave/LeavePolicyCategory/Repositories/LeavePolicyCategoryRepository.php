<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicyCategory\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Ramsey\Uuid\UuidInterface;
use Modules\Leave\LeavePolicyCategory\Models\LeavePolicyCategory;

/**
 * @property LeavePolicyCategory $model
 */
class LeavePolicyCategoryRepository extends BaseRepository
{
    public function __construct(LeavePolicyCategory $model)
    {
        parent::__construct($model);
    }

    public function getLeavePolicyCategory(UuidInterface $id): LeavePolicyCategory
    {
        return $this->model
            ->where('id', $id->toString())
            ->firstOrFail();
    }

    public function createLeavePolicyCategory(array $data): LeavePolicyCategory
    {
        return $this->create($data);
    }

    public function updateLeavePolicyCategory(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteLeavePolicyCategory(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

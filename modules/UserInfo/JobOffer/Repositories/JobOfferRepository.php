<?php

declare(strict_types=1);

namespace Modules\UserInfo\JobOffer\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\UserInfo\JobOffer\Models\JobOffer;

/**
 * @property JobOffer $model
 * @method JobOffer findOneOrFail($id)
 * @method JobOffer findOneByOrFail(array $data)
 */
class JobOfferRepository extends BaseRepository
{
    public function __construct(JobOffer $model)
    {
        parent::__construct($model);
    }

    public function getJobOfferList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getJobOffer(UuidInterface $companyId, UuidInterface $globalId): ?JobOffer
    {
        return $this->model->where([
            'global_id' => $globalId,
            'company_id' => $companyId,
        ])->first();
    }
    public function createOrUpdateJobOffer(array $data): JobOffer
    {
        $jobOffer = $this->model->where([
            'global_id' => $data['global_id'],
            'company_id' => $data['company_id'],
        ])->first();

        if ($jobOffer) {
            $jobOffer->update($data);
            return $jobOffer;
        }

        return $this->model->create($data);
    }

    public function createJobOffer(array $data): JobOffer
    {
        return $this->create($data);
    }

    public function updateJobOffer(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteJobOffer(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

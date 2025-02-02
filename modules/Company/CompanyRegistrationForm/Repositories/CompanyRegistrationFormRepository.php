<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationForm\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Company\CompanyRegistrationForm\Models\CompanyRegistrationForm;

/**
 * @property CompanyRegistrationForm $model
 * @method CompanyRegistrationForm findOneOrFail($id)
 * @method CompanyRegistrationForm findOneByOrFail(array $data)
 */
class CompanyRegistrationFormRepository extends BaseRepository
{
    public function __construct(CompanyRegistrationForm $model)
    {
        parent::__construct($model);
    }

    public function getCompanyRegistrationFormList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getCompanyRegistrationForm(UuidInterface $id): CompanyRegistrationForm
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createCompanyRegistrationForm(array $data): CompanyRegistrationForm
    {
        return $this->create($data);
    }

    public function updateCompanyRegistrationForm(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteCompanyRegistrationForm(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

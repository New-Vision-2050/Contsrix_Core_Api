<?php

declare(strict_types=1);

namespace Modules\Company\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Company\CompanyRegistrationForm\Models\CompanyRegistrationForm;
use Ramsey\Uuid\UuidInterface;
use Modules\Company\Models\Company;
use Carbon\Carbon;

/**
 * @property Company $model
 * @method Company findOneOrFail($id)
 * @method Company findOneByOrFail(array $data)
 */
class CompanyRepository extends BaseRepository
{
    public function __construct(Company $model)
    {
        parent::__construct($model);
    }

    public function getCompanyList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getCompany(UuidInterface $id): Company
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createCompany(array $data): Company
    {
        return $this->create($data);
    }

    public function updateCompany(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteCompany(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
    public function isEmailExists(string $email): bool
    {
        return Company::where('email', $email)->exists();
    }
    public function isPhoneExists(string $phone): bool
    {
        return Company::where('phone', $phone)->exists();
    }
    public function isRegistrationExists(string $registration_no): bool
    {
        return CompanyRegistrationForm::where('registration_no', $registration_no)->exists();
    }
    public function totalCompany(): int
    {
        return Company::count();
    }
    public function activeCompany(): int
    {
        return Company::where('is_active',1)->count();
    }
    public function completeDataCompany(): int
    {
        return Company::where('complete_data',1)->count();
    }
    public function dateActivateCompany(): int
    {
        return Company::where('date_activate', '>=', Carbon::today())
        ->where('date_activate', '<', Carbon::today()->addDays(30))
        ->count();
    }


}

<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Company\CompanyCore\CompanyRegistrationForm\Models\CompanyRegistrationForm;
use Ramsey\Uuid\UuidInterface;
use Modules\Company\CompanyCore\Models\Company;
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
    public function isClassificationExists(string $classification_no): bool
    {
        return CompanyRegistrationForm::where('classification_no', $classification_no)->exists();
    }

    public function totalCompany(?Carbon $date = null): int
    {
        if ($date) {
            return Company::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
        }
        return Company::count();
    }

    public function activeCompany(?Carbon $date = null): int
    {
        if ($date) {
            return Company::where('is_active', 1)
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
        }
        return Company::where('is_active', 1)->count();
    }

    public function completeDataCompany(?Carbon $date = null): int
    {
        if ($date) {
            return Company::where('complete_data', 1)
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
        }
        return Company::where('complete_data', 1)->count();
    }

    public function dateActivateCompany(?Carbon $date = null): int
    {
        if ($date) {
            return Company::whereYear('date_activate', $date->year)
                ->whereMonth('date_activate', $date->month)
                ->count();
        }
        return Company::where('date_activate', '>=', Carbon::today())
            ->where('date_activate', '<', Carbon::today()->addDays(30))
            ->count();

    }
}

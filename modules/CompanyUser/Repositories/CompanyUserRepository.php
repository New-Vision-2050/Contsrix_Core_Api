<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Carbon\Carbon;
use Composer\Autoload\ClassLoader;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\CompanyUser\Enum\CompanyUserStatus;
use Modules\CompanyUser\Models\CompanyUserCompany;
use Ramsey\Uuid\UuidInterface;
use Modules\CompanyUser\Models\CompanyUser;

/**
 * @property CompanyUser $model
 * @method CompanyUser findOneOrFail($id)
 * @method CompanyUser findOneByOrFail(array $data)
 */
class CompanyUserRepository extends BaseRepository
{

    public function __construct(CompanyUser $model)
    {
        parent::__construct($model);
    }

    public function withRelations(array $relations = [], $page = 1, $perPage = 15)
    {
        if (method_exists($this->model, 'scopeFilter')) {
            $query = $this->model->filter(request()->all())->with($relations);
        }else{
            $query = $this->model->with($relations);
        }

        $count = $query->count();
        $paginatedData = $query->forPage($page, $perPage)->get();
        $paginationArray = $this->getPaginationInformation($page, $perPage, $count);

        return [
            'pagination' => $paginationArray,
            'data' => $paginatedData,
        ];


    }

    public function getCompanyUserCount(Carbon $date = null)
    {


        return $this->model->when($date != null, function ($query) use ($date) {
            $query->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month);
        })->count();

    }

    public function getActiveInactiveCompanyUserCount(Carbon $date = null, $status = CompanyUserStatus::ACTIVE->value)
    {
        return $this->model->when($date != null, function ($query) use ($date) {
            $query->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month);
        })->when($status == CompanyUserStatus::ACTIVE->value, function ($q) { //one active mean user active
            $q->whereHas("companies", function ($query) {
                $query->where("company_users_companies.status", CompanyUserStatus::ACTIVE->value);
            });
        })->when($status == CompanyUserStatus::INACTIVE->value, function ($q) { //use does not have any active and inactive
            $q->WhereDoesntHave("companies", function ($query) {
                $query->where("company_users_companies.status", CompanyUserStatus::ACTIVE->value)->orWhere("company_users_companies.status", CompanyUserStatus::PENDING->value);
            });
        })->when($status == CompanyUserStatus::PENDING->value, function ($q) {//user would have at least one pending and does not have any active rolr
            $q->WhereDoesntHave("companies", function ($query) {
                $query->where("company_users_companies.status", CompanyUserStatus::ACTIVE->value);
            })->whereHas("companies", function ($query) {
                $query->where("company_users_companies.status", CompanyUserStatus::PENDING->value);
            });
        })->count();
    }


    public function deleteCompanyUserRole(
        UuidInterface $companyUserId,
        UuidInterface $companyId,
        int           $role): void
    {
        try {
            DB::beginTransaction();
            CompanyUserCompany::where('company_user_id', $companyUserId)
                ->where('company_id', $companyId)
                ->where('role', $role)
                ->delete();
            if (CompanyUserCompany::where('company_user_id', $companyUserId)->count() == 0) {
                $this->delete($companyUserId);
            }
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            throw new \Exception(__("validation.delete-not-successful"), 500);
        }

    }

    public function getCompanyUserList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getCompanyUser(UuidInterface $id): CompanyUser
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function findByEmail(string $email)
    {
        return $this->findOneBy([
            'email' => $email,
        ]);
    }

    public function findByPhone(string $phone)
    {
        return $this->findOneBy([
            'phone' => $phone,
        ]);
    }

    public function getCompanyUserBy(array $by): CompanyUser
    {
        return $this->findOneBy($by);
    }

    public function createCompanyUser(array $companyUserData, array $companyRole): CompanyUser
    {
        try {
            DB::beginTransaction();
            $companyUser = $this->create($companyUserData);
            CompanyUserCompany::create($companyRole + ["company_user_id" => $companyUser->id]);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            throw new \Exception(__("validation.create-not-successful"), 500);
        }

        return $companyUser;
    }


    public function assignRoleCompanyUser(UuidInterface $id, array $companyUserRoleData): void
    {
        CompanyUserCompany::firstOrCreate($companyUserRoleData + ["company_user_id" => $id], $companyUserRoleData + ["company_user_id" => $id]);
    }

    public function updateCompanyUser(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteCompanyUser(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

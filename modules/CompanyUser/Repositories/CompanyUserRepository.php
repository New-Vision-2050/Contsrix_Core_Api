<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Repositories;

use App\Exceptions\CustomException;
use BasePackage\Shared\Repositories\BaseRepository;
use Carbon\Carbon;
use Composer\Autoload\ClassLoader;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Enum\CompanyUserStatus;
use Modules\CompanyUser\Models\ClientDetail;
use Modules\CompanyUser\Models\CompanyUserAddress;
use Modules\CompanyUser\Models\CompanyUserCompany;
use Modules\CompanyUser\Models\CompanyUserCompanyManagementHierarchy;
use Modules\JobTitle\Models\JobTitle;
use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;
use Ramsey\Uuid\UuidInterface;
use Modules\CompanyUser\Models\CompanyUser;
use function Laravel\Prompts\table;

/**
 * @property CompanyUser $model
 * @method CompanyUser findOneOrFail($id)
 * @method CompanyUser findOneByOrFail(array $data)
 */
class CompanyUserRepository extends BaseRepository
{

    public function __construct(CompanyUser $model, private UserRepository $userRepository)
    {
        parent::__construct($model);
    }

    public function withRelationsFilterByType(array $relations = [], $page = 1, $perPage = 15, $type = null, $companyId = null, $branchId = null)
    {
        if (method_exists($this->model, 'scopeFilter')) {
            $query = $this->model->filter(request()->all())->with($relations);
        } else {
            $query = $this->model->with($relations);
        }
        $query = $query->when($type != null, function ($query) use ($type) {
            $query->whereHas("companies", function ($query) use ($type) {
                $query->where("company_users_companies.role", $type);
            });
        })->when($companyId != null, function ($query) use ($companyId) {
            $query->whereHas("companies", function ($query) use ($companyId) {

                $query->where("companies.id", $companyId);
            });
        });//TODO filter with branches very important

        $count = $query->count();
        $paginatedData = $query->forPage($page, $perPage)->get();
        $paginationArray = $this->getPaginationInformation($page, $perPage, $count);
        return array_merge($paginationArray, [
            'data' => $paginatedData
        ]);
    }

    public
    function getCompanyUserCount(Carbon $date = null)
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
            $companyUser = $this->findOneByOrFail(['id' => $companyUserId]);
            CompanyUserCompany::where('global_company_user_id', $companyUser->global_id)
                ->where('company_id', $companyId)
                ->where('role', $role)
                ->delete();
            if (CompanyUserCompany::where('global_company_user_id', $companyUser->global_id)->where('company_id', $companyId)->count() == 0) {
                $this->userRepository->deleteWhere(["global_company_user_id" => $companyUser->global_id, "company_id" => $companyId]);

            }
            if (CompanyUserCompany::where('global_company_user_id', $companyUser->global_id)->count() == 0) {
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

    public function getCompanyUserGlobalId(UuidInterface $global_id): CompanyUser
    {
        return $this->findOneByOrFail([
            'global_id' => $global_id->toString(),
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

    private function getPhoneNumberInfo(string $phone): array
    {
        $phoneArray = explode(' ', $phone);
        return [
            'phone_code' => str_replace("+", "", $phoneArray[0]),
            'phone' => str_replace(" ", "", $phone),
        ];
    }

    public
    function createCompanyUser(array $companyUserData, array $companyRole, array $branches = null, array $address = null, array $clientDetail = null): CompanyUser
    {
        try {
            $phone = $this->getPhoneNumberInfo($companyUserData['phone']);

            DB::beginTransaction();
            $companyUser = $this->model->withTrashed()->withoutParentModel()->where("email", $companyUserData['email'])->first();
            if (!$companyUser) {

                $companyUser = $this->create($companyUserData);
            }
            $companyUser->restore();

            $companyUser->update(["global_id" => $companyUser->id]);//set global id we can make different logic  in the future
            $companyUser = $companyUser->fresh();//get updated data for company user
            $user = $this->userRepository->model->withTrashed()->withoutTenancy()->where(["global_company_user_id" => $companyUser->global_id, "company_id" => $companyRole['company_id']])->first();
            $mainBranchId = ManagementHierarchy::query()->where("company_id", $companyRole['company_id'])->where("parent_id", null)->first()->id;
            $mainManagement = ManagementHierarchy::query()->where("company_id", $companyRole['company_id'])->where("parent_id", $mainBranchId)->where("type","management")->first();
            if ($branches != null && CompanyUserRole::EMPLOYEE->value == $companyRole['role']) {
                $mainManagement = ManagementHierarchy::query()->where("company_id", $companyRole['company_id'])->where("parent_id", $branches[0])->where("type","management")->first();

            }
            if (!$user) {//must create user if use api createCompanyUser because validation prevent replicate


                $usersInCompanyCount = Company::query()->where("id", $companyRole['company_id'])->first()->users()->count();

                $user = $this->userRepository->createUser(array_merge([
                    'name' => $companyUserData['name'],
                    'email' => $companyUserData['email'],
                    'company_id' => $companyRole['company_id'],
                    "global_company_user_id" => $companyUser->global_id,
                    "is_owner" => $usersInCompanyCount == 0 ? 1 : 0,
                    "management_hierarchy_id" => $companyRole['role'] == CompanyUserRole::EMPLOYEE->value ? $mainManagement->id : null,
                ], $phone));

            } else {
                $user->restore();
                $user->fresh();

            }
            $companyUserCompany = CompanyUserCompany::query()->withTrashed()->withoutTenancy()->where("role", $companyRole['role'])->where("global_company_user_id", $companyUser->global_id)->where('company_id', $companyRole['company_id'])->first();
            if (!$companyUserCompany) {
                $companyUserCompany = CompanyUserCompany::create($companyRole + ["global_company_user_id" => $companyUser->id]);

            } else {
                if ($companyUserCompany->deleted_at != null) {
                    $companyUserCompany->restore();

                }
            }

            //replace when user in specifice role branches
            CompanyUserCompanyManagementHierarchy::query()->where("company_user_company_id", $companyUserCompany->id)->delete();
            if ($branches != null) {

                foreach ($branches as $branch)
                    CompanyUserCompanyManagementHierarchy::query()->create(
                        [
                            "user_id" => $user->id,
                            "management_hierarchy_id" => $branch,
                            "company_user_company_id" => $companyUserCompany->id
                        ]
                    );
            } elseif (CompanyUserRole::EMPLOYEE->value == $companyRole['role']) {//
                CompanyUserCompanyManagementHierarchy::query()->create(
                    [
                        "user_id" => $user->id,
                        "management_hierarchy_id" => $mainBranchId,
                        "company_user_company_id" => $companyUserCompany->id
                    ]
                );

            }
            if ($address != null) {
                CompanyUserAddress::query()->updateOrCreate(["global_company_user_id" => $companyUser->id], $address + ["global_company_user_id" => $companyUser->id]);
            }
            if (CompanyUserRole::EMPLOYEE->value == $companyRole['role']) {
                $userProfessionalData = UserProfessionalData::query()->where([
                    'global_id' => $user->global_company_user_id,
                    'company_id' => $companyRole['company_id'],
                ])->first();
                $data = [
                    'company_id' => $companyRole['company_id'],
                    'global_id' => $user->global_company_user_id,
                    'branch_id' => $branches != null ? $branches[0] : $mainBranchId,
                    'management_id' => $mainManagement->id,
                    "job_title_id"=>isset($companyRole["job_title_id"])?$companyRole["job_title_id"]:null,
                    "job_type_id"=>isset($companyRole["job_title_id"])?JobTitle::query()->where("id", $companyRole["job_title_id"])->first()->job_type_id : null,

                ];
                if ($userProfessionalData) {
                    $userProfessionalData->update($data);

                } else {
                    $userProfessionalData = UserProfessionalData::create($data);
                }

            }

            if (CompanyUserRole::CLIENT->value == $companyRole['role']) {
                ClientDetail::query()->updateOrCreate(["user_id" => $user->id], $clientDetail + ["user_id" => $user->id]);
            }


            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            throw new CustomException($exception->getMessage(), 400);
        }

        return $companyUser;
    }


    public function setAddress(array $addressData)
    {
        return CompanyUserAddress::query()->create($addressData);
    }


    public
    function assignRoleCompanyUser(UuidInterface $id, array $companyUserRoleData, array $branches = null): void
    {
        try {
            DB::beginTransaction();
            $companyUser = $this->findOneBy(["id" => $id]);
            $user = $this->userRepository->findOneBy(["global_company_user_id" => $companyUser->global_id, "company_id" => $companyUserRoleData["company_id"]]);
            $mainBranchId = ManagementHierarchy::query()->where("company_id", $companyUserRoleData["company_id"])->where("parent_id", null)->first()->id;
            $mainManagement = ManagementHierarchy::query()->where("company_id", $companyUserRoleData["company_id"])->where("parent_id", $mainBranchId)->where("type","management")->first();
            if ($branches != null && CompanyUserRole::EMPLOYEE->value == $companyUserRoleData['role']) {
                $mainBranchId = $branches[0];
                $mainManagement = ManagementHierarchy::query()->where("company_id", $companyUserRoleData['company_id'])->where("parent_id", $branches[0])->where("type","management")->first();


            }
            if (!$user) {
                $user = $this->userRepository->findOneBy(["global_company_user_id" => $companyUser->global_id]);

                //create user in company assigned to main management
                if ($user) {
                    $usersInCompanyCount = Company::query()->where("id", $companyUserRoleData["company_id"])->first()->users()->count();
                    $newUser = $user->replicate();
                    $newUser->password = null; // make password null
                    $newUser->company_id = $companyUserRoleData["company_id"];
                    $newUser->is_owner = $usersInCompanyCount == 0 ? 1 : 0;
                    $newUser->management_hierarchy_id = $companyUserRoleData['role'] == CompanyUserRole::EMPLOYEE->value ? $mainManagement->id : null;

                    $newUser->save();
                } else {
                    $usersInCompanyCount = Company::query()->where("id", $companyUserRoleData["company_id"])->first()->users()->count();

                    $this->userRepository->createUser([
                        'name' => $companyUser->first_name . ' ' . $companyUser->last_name,
                        'email' => $companyUser->email,
                        'company_id' => $companyUserRoleData["company_id"],
                        "phone" => $companyUser->phone,
                        "phone_code" => $companyUser->phone_code,
                        "global_company_user_id" => $companyUser->global_id,
                        "is_owner" => $usersInCompanyCount == 0 ? 1 : 0,
                        "management_hierarchy_id" => $companyUserRoleData['role'] == CompanyUserRole::EMPLOYEE->value ? $mainManagement->id : null,
                    ]);
                }

            }
            $companyUserCompany = CompanyUserCompany::firstOrCreate($companyUserRoleData + ["global_company_user_id" => $companyUser->global_id], $companyUserRoleData + ["global_company_user_id" => $companyUser->global_id]);
            if (CompanyUserRole::EMPLOYEE->value == $companyUserRoleData['role']) {
                $userProfessionalData = UserProfessionalData::query()->where([
                    'global_id' => $user->global_company_user_id,
                    'company_id' => $companyUserRoleData['company_id'],
                ])->first();
                $data = [
                    'company_id' => $companyUserRoleData['company_id'],
                    'global_id' => $user->global_company_user_id,
                    'branch_id' => $mainBranchId,
                    'management_id' => $mainManagement->id,

                ];
                if ($userProfessionalData) {
                    $userProfessionalData->update($data);

                } else {
                    $userProfessionalData = UserProfessionalData::create($data);

                }

            }

            if (CompanyUserRole::EMPLOYEE->value == $companyUserRoleData['role']) {
                CompanyUserCompanyManagementHierarchy::query()->create(
                    [
                        "user_id" => $user->id,
                        "management_hierarchy_id" => $mainBranchId,
                        "company_user_company_id" => $companyUserCompany->id
                    ]
                );

            } elseif ($branches != null) {

                foreach ($branches as $branch)
                    CompanyUserCompanyManagementHierarchy::query()->create(
                        [
                            "user_id" => $user->id,
                            "management_hierarchy_id" => $branch,
                            "company_user_company_id" => $companyUserCompany->id
                        ]
                    );
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), 500);
        }
    }

    public function updateCompanyUser(UuidInterface $id, array $data): bool
    {
        try {
            DB::beginTransaction();
            $phoneInfo = $this->getPhoneNumberInfo($data['phone']);


            $companyUser = $this->findOneBy(["id" => $id]);
            $users = $this->userRepository->updateWhere(["global_company_user_id" => $companyUser->global_id], array_merge([
                "name" => $data["name"],
                "email" => $data["email"],
                "global_company_user_id" => $companyUser->global_id
            ], $phoneInfo));

            $this->update($id, array_merge($data, $phoneInfo));
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), 500);
        }
        return true;

    }


    public function updateCompanyUserDataInfo(UuidInterface $global_id, array $data): bool
    {
        $this->updateWhere(["global_id" => $global_id], $data);

        $users = $this->userRepository->updateWhere(
            ["global_company_user_id" => $global_id],
            ['name' => $data['name'] ?? null]
        );

        return true;
    }

    public function updateCompanyUserIdentityData(UuidInterface $global_id, array $data): bool
    {
        $this->updateWhere(["global_id" => $global_id], $data);

        if (isset($data['email'])) {
            $this->userRepository->updateWhere(
                ["global_company_user_id" => $global_id],
                ['email' => $data['email']]
            );
        }

        if (isset($data['phone'])) {
            $this->userRepository->updateWhere(
                ["global_company_user_id" => $global_id],
                ['phone' => $data['phone']]
            );
        }

        return true;
    }


    public
    function updateUserData(UuidInterface $userId, array $data)
    {
        $this->userRepository->updateWhere(
            ["id" => $userId], $data
        );

        return true;
    }


    public
    function deleteCompanyUser(UuidInterface $id): bool
    {
        try {
            DB::beginTransaction();
            $companyUser = $this->findOneBy(["id" => $id]);
            CompanyUserCompany::query()->where(["global_company_user_id" => $companyUser->global_id])->delete();
            $this->delete($id);
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(__("validation.delete-not-successful"), 500);
        }
        return true;
    }

    public
    function getIdsWithRelations($ids = [], $relations = [])
    {
        return $this->model->with($relations)->whereIn("id", $ids)->get();
    }

    public
    function getAllWithRelations($relations = [])
    {
        return $this->model->with($relations)->get();
    }


}

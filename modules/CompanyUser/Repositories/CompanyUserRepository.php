<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Repositories;

use App\Exceptions\CustomException;
use BasePackage\Shared\Repositories\BaseRepository;
use Carbon\Carbon;
use Composer\Autoload\ClassLoader;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Repositories\AttendanceConstraintRepository;
use Modules\Attendance\Services\AutoAttendanceService;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\Company\ManagementHierarchy\DTO\AssignUsersToManagementHierarchyDTO;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;
use Modules\Company\ManagementHierarchy\Repositories\UserCanAccessManagementHierarchyRepository;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Enum\CompanyUserStatus;
use Modules\CompanyUser\Models\ClientDetail;
use Modules\CompanyUser\Models\CompanyUserAddress;
use Modules\CompanyUser\Repositories\BrokerDetailRepository;
use Modules\CompanyUser\Models\CompanyUserCompany;
use Modules\CompanyUser\Models\CompanyUserCompanyManagementHierarchy;
use Modules\JobTitle\Models\JobTitle;
use Modules\JobTitle\Repositories\JobTitleRepository;
use Modules\RoleAndPermission\Models\Role;
use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;
use Modules\UserInfo\UserProfessionalData\Repositories\UserProfessionalDataRepository;
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

    public function __construct(
        CompanyUser                                        $model,
        private UserRepository                             $userRepository,
        private JobTitleRepository                         $jobTitleRepository,
        private CompanyRepository                          $companyRepository,
        private ManagementHierarchyRepository              $managementHierarchyRepository,
        private UserProfessionalDataRepository             $userProfessionalDataRepository,
        private CompanyUserCompanyRepository               $companyUserCompanyRepository,
        private CompanyUserAddressRepository               $companyUserAddressRepository,
        private ClientDetailRepository                     $clientDetailRepository,
        private BrokerDetailRepository                     $brokerDetailRepository,
        private CompanyUserManagementHierarchyRepository   $companyUserManagementHierarchyRepository,
        private AttendanceConstraintRepository             $attendanceConstraintRepository,
        private AutoAttendanceService                      $autoAttendanceService,
        private UserCanAccessManagementHierarchyRepository $userCanAccessManagementHierarchyRepository,

    )
    {

        parent::__construct($model);
    }

    public function getModel()
    {
        return $this->model;
    }

    public function withRelationsFilterByType(array $relations = [], $page = 1, $perPage = 15, $type = null, $companyId = null, $branchId = null)
    {
        if (method_exists($this->model, 'scopeFilter')) {
            $query = $this->model->filter(request()->all())->with($relations);
        } else {
            $query = $this->model->with($relations);
        }
        $query = $query->when($type != null, function ($query) use ($type) {
            $query->whereHas("users.companyUserCompanies", function ($query) use ($type) {
                $query->where("role", $type);
            });
        })->when(request()->has('sub_entity_id'), function ($query) use ($type) {
            $query->whereHas("users.companyUserCompanies", function ($query) use ($type) {
                $query->where("sub_entity_id", request()->sub_entity_id);
            });
        })

            ->when($companyId != null, function ($query) use ($companyId) {
            $query->whereHas("companies", function ($query) use ($companyId) {

                $query->where("companies.id", $companyId);
            });
        })->when($branchId != null, function ($query) use ($branchId, $type) {
            $query->whereHas('users', function ($userQuery) use ($branchId, $type) {
                $userQuery->whereHas('roleAndBranches', function ($hierarchyQuery) use ($branchId, $type) {
                    $hierarchyQuery->where('management_hierarchy_id', $branchId)
                        ->when($type != null, function ($q) use ($type) {
                            $q->whereHas('companyUserCompany', function ($companyUserCompanyQuery) use ($type) {
                                $companyUserCompanyQuery->where('role', $type);
                            });
                        });
                });
            });
        })->orderBy("created_at", "desc");

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
            $q->whereHas("users.companyUserCompanies", function ($query) {
                $query->where("status", CompanyUserStatus::ACTIVE->value);
            });
        })->when($status == CompanyUserStatus::INACTIVE->value, function ($q) { //use does not have any active and inactive
            $q->WhereDoesntHave("users.companyUserCompanies", function ($query) {
                $query->where("status", CompanyUserStatus::ACTIVE->value)->orWhere("status", CompanyUserStatus::PENDING->value);
            });
        })->when($status == CompanyUserStatus::PENDING->value, function ($q) {//user would have at least one pending and does not have any active rolr
            $q->WhereDoesntHave("users.companyUserCompanies", function ($query) {
                $query->where("status", CompanyUserStatus::ACTIVE->value);
            })->whereHas("users.companyUserCompanies", function ($query) {
                $query->where("status", CompanyUserStatus::PENDING->value);
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
            $companyUser = $this->findOneBy(['id' => $companyUserId]);
            $this->canDelete($companyUser);


            $this->companyUserCompanyRepository->deleteWhere(["global_company_user_id" => $companyUser->global_id, "company_id" => $companyId, "role" => $role]);
            if ($this->companyUserCompanyRepository->countWhere(["global_company_user_id" => $companyUser->global_id, "company_id" => $companyId]) == 0) {
                $this->userRepository->deleteWhere(["global_company_user_id" => $companyUser->global_id, "company_id" => $companyId]);

            }
            if ($this->companyUserCompanyRepository->countWhere(["global_company_user_id" => $companyUser->global_id]) == 0) {
                $this->delete($companyUserId);
            }
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            throw new \Exception(__("validation.delete-not-successful"), 500);
        }

    }

    public function deleteUserRole(UuidInterface $userId, int $role)
    {
        try {
            DB::beginTransaction();

            // Get user and company_id from users table
            $user = $this->userRepository->find($userId);

            if (!$user) {
                throw new \Exception(__("validation.user-not-found"), 404);
            }

            $companyId = $user->company_id;
            if (!$companyId) {
                throw new \Exception(__("validation.user-has-no-company"), 400);
            }

            // Find the company user by global_company_user_id
            $companyUser = $this->findOneBy(['global_id' => $user->global_company_user_id]);
            if ($companyUser->email === 'admin@constrix-nv.com') {
                throw new CustomException(__("validation.admin_account_cannot_be_deleted"), 400);
            }

            // Check if trying to delete self
            $currentUserId = auth()->user()->global_company_user_id ?? null;
//            if ($currentUserId && $currentUserId === $companyUser->global_id) {
//                throw new CustomException(__("validation.cannot_delete_yourself"), 400);
//            }

            // Check if trying to delete company owner
            $isOwner = $user->is_owner;
            if ($isOwner && $role === CompanyUserRole::EMPLOYEE->value) {
                throw new CustomException(__("validation.cannot_delete_company_owner"), 400);
            }

            if ($role == CompanyUserRole::CLIENT->value && count( $user->clientRequests))
            {
                throw new CustomException("this user has client requests ", 400);

            }

            $this->companyUserCompanyRepository->deleteWhere(["global_company_user_id" => $companyUser->global_id, "company_id" => $companyId, "role" => $role]);

            // Soft delete user_professional_datas if Employee role is being deleted
            if ($role === CompanyUserRole::EMPLOYEE->value) {
                $professionalData = UserProfessionalData::where('global_id', $companyUser->global_id)
                    ->where('company_id', $companyId)
                    ->first();

                if ($professionalData) {
                    $professionalData->delete(); // Soft delete
                }
            }

            if ($this->companyUserCompanyRepository->countWhere(["global_company_user_id" => $companyUser->global_id, "company_id" => $companyId]) == 0) {
                $this->userRepository->deleteWhere(["global_company_user_id" => $companyUser->global_id, "company_id" => $companyId]);

            }

            if ($this->companyUserCompanyRepository->countWhere(["global_company_user_id" => $companyUser->global_id]) == 0) {
                $this->model->withoutParentModel()->find($companyUser->id)->delete();
            }

            DB::commit();

            // Return the stored data instead of the potentially deleted record
            return $companyUser;

        } catch (\Exception $exception) {
            DB::rollBack();
            throw new \Exception($exception->getMessage(), 400);
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
        return $this->model->withoutParentModel()->where("email", $email)->first();

    }

    public function getCompaniesByEmail(string $email)
    {
        return $this->model->withoutParentModel()
            ->with(['companies' => function ($query) {
                $query->withoutTenancy()
                    ->with('domains');
            }])
            ->where('email', $email)
            ->first();
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


    public function createCompanyUser(array $companyUserData, array $companyRole, array $branches = null, array $address = null, array $clientDetail = null, array $brokerDetail = null)
    {
        try {
            $phone = $this->getPhoneNumberInfo($companyUserData['phone']);

            DB::beginTransaction();
            $generalManagerJobTitle = $this->jobTitleRepository->model->withoutTenancy()->where(["type" => "general_manager", "company_id" => $companyRole['company_id']])->first();
            if (isset($companyUserData["job_title_id"]) && $companyUserData["job_title_id"] && $companyUserData["job_title_id"] != null) {
                $companyIdJobTitle = $this->jobTitleRepository->model->withoutTenancy()->where(["id" => $companyUserData["job_title_id"]])->first()->company_id;
                if ($companyRole['company_id'] != $companyIdJobTitle) {
                    $companyUserData["job_title_id"] = $generalManagerJobTitle->id;
                }
            }
//if client organization type is 2 then create user in same company and temp new company
            if (CompanyUserRole::CLIENT->value == $companyRole['role'] && $clientDetail !== null) {

                if ($clientDetail["type"] == 2) {
                    $newCompanyClientId = $companyRole["company_id"];
                    $companyRole["company_id"] = tenant("id");

                }
            }

            if (CompanyUserRole::BROKER->value == $companyRole['role'] && $brokerDetail !== null) {

                if ($brokerDetail["type"] == 2) {

                    $newCompanyClientId = $companyRole["company_id"];
                    $companyRole["company_id"] = tenant("id");

                }
            }

            // Find or create company user
            $companyUser = $this->findOrCreateCompanyUser(array_merge($companyUserData, $phone));
            $companyUser->phone = $phone['phone'];
            $companyUser->phone_code = $phone['phone_code'];

            // Find or create user in the company
            $user = $this->findOrCreateUserInCompany(
                $companyUser,
                $companyRole['company_id'],
                $companyUserData['name'],
                $companyRole['role'],
                $branches
            );

            // Handle owner permissions if necessary
            $this->handleOwnerPermissions($user, $companyRole['company_id']);

            // Create or update company user role
            $companyUserCompany = $this->companyUserCompanyRepository->createOrRestore($companyRole + ["global_company_user_id" => $companyUser->global_id]);


            // Handle branch assignments
            $mainBranchId = $this->handleBranchAssignments($user, $companyUserCompany, $companyRole, $branches);

            // Handle additional data based on role
            if (CompanyUserRole::EMPLOYEE->value == $companyRole['role']) {
                $this->handleEmployeeData($user, $companyRole['company_id'], $mainBranchId, $companyUserData);
            }
            $userBranchId = auth()->user()?->professionalData?->branch_id;
            if ($userBranchId == null) {
                $userBranchId = $mainBranchId;
            }
            // Handle address if provided
            if ($address !== null) {
                $this->companyUserAddressRepository->updateOrCreate(
                    ["global_company_user_id" => $companyUser->id],
                    $address + ["global_company_user_id" => $companyUser->id]
                );
            }

            // Handle client details if client role
            if (CompanyUserRole::CLIENT->value == $companyRole['role'] && $clientDetail !== null) {
                $clientDetail = $this->clientDetailRepository->updateOrCreate(
                    ["user_id" => $user->id],
                    $clientDetail + ["user_id" => $user->id]
                );


                if ($clientDetail["type"] == 2) {
                    $user = $this->findOrCreateUserInCompany(
                        $companyUser,
                        $newCompanyClientId,
                        $companyUserData['name'],
                        $companyRole['role']
                    );
                    $companyUserCompany = $this->companyUserCompanyRepository->createOrRestore(array_merge($companyRole, ["global_company_user_id" => $companyUser->global_id, "company_id" => $newCompanyClientId]));
                    $clientDetail->update(["company_id" => $newCompanyClientId, "original_branch_id" => $userBranchId, "is_created_by_owner" => auth()->user()->is_owner || auth()->user()->email == "admin@constrix-nv.com"]);
                }
            }
            // Handle broker details if broker role
            if (CompanyUserRole::BROKER->value == $companyRole['role'] && $brokerDetail !== null) {

                $brokerDetail = $this->brokerDetailRepository->updateOrCreate(
                    ["user_id" => $user->id],
                    $brokerDetail + ["user_id" => $user->id, "company_id" => $companyRole['company_id']]
                );
                if ($brokerDetail["type"] == 2) {
                    $user = $this->findOrCreateUserInCompany(
                        $companyUser,
                        $newCompanyClientId,
                        $companyUserData['name'],
                        $companyRole['role']
                    );
                    $companyUserCompany = $this->companyUserCompanyRepository->createOrRestore(array_merge($companyRole, ["global_company_user_id" => $companyUser->global_id, "company_id" => $newCompanyClientId]));
                    $brokerDetail->update(["company_id" => $newCompanyClientId, "original_branch_id" => $userBranchId, "is_created_by_owner" => auth()->user()->is_owner || auth()->user()->email == "admin@constrix-nv.com"]);
                }
            }
//
            DB::commit();
            return $companyUser;
        } catch (\Exception $exception) {
            DB::rollBack();
            throw new CustomException($exception->getMessage(), 400);
        }
    }

    public function assignRoleCompanyUser(UuidInterface $id, array $companyUserRoleData, array $branches = null): void
    {
        try {
            DB::beginTransaction();

            $companyUser = $this->model->withoutParentModel()->where(["id" => $id])->first();

            // Find or create user in the company
            $user = $this->findOrCreateUserInCompany(
                $companyUser,
                $companyUserRoleData['company_id'],
                $companyUser->name,
                $companyUserRoleData['role'],
                $branches
            );

            // Handle owner permissions if necessary
            $this->handleOwnerPermissions($user, $companyUserRoleData['company_id']);

            // Create company user role
            $companyUserCompany = $this->companyUserCompanyRepository->createOrRestore(
                $companyUserRoleData + ["global_company_user_id" => $companyUser->global_id]
            );

            // Handle employee role data and branch associations
            if (CompanyUserRole::EMPLOYEE->value == $companyUserRoleData['role']) {
                // Get main branch ID based on branches parameter
                $mainBranchData = $this->getMainBranchData($companyUserRoleData['company_id'], $branches);
                $this->handleEmployeeData($user, $companyUserRoleData['company_id'], $mainBranchData['branchId']);
                // Create branch association for employee
                $this->createBranchAssociation($user, $companyUserCompany, $mainBranchData['branchId']);
            } elseif ($branches !== null) {
                // Create branch associations for other roles with branches
                $this->createMultiBranchAssociations($user, $companyUserCompany, $branches);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * Find existing company user or create a new one
     */
    private function findOrCreateCompanyUser(array $companyUserData): CompanyUser
    {
        $companyUser = $this->model->withTrashed()->withoutParentModel()->where("email", $companyUserData['email'])->first();

        if (!$companyUser) {
            $companyUser = $this->create($companyUserData);
            $companyUser->update(["global_id" => $companyUser->id]);
        } elseif ($companyUser->deleted_at !== null) {

            $companyUser->restore();
        }
        $companyUser->update($companyUserData);

        return $companyUser->fresh();
    }

    /**
     * Find or create user within a company
     */
    private function findOrCreateUserInCompany(CompanyUser $companyUser, $companyId, string $name, $role, ?array $branches = null): User
    {
        // Try to find existing user in company
        $user = $this->userRepository->model->withoutTenancy()->where([
            "global_company_user_id" => $companyUser->global_id,
            "company_id" => $companyId
        ])->withTrashed()->first();

        $mainBranchData = $this->getMainBranchData($companyId, $branches);

        if (!$user) {
            // Get main branch data

            // Try to find user in any company
            $existingUser = $this->userRepository->model->withoutTenancy()->where([
                "global_company_user_id" => $companyUser->global_id
            ])->first();

            $usersInCompanyCount = $this->companyRepository->model->withoutTenancy()->where(["id" => $companyId])->first()?->users()->where("is_owner", 1)->count();
            $isOwner = $usersInCompanyCount === 0 ? 1 : 0;

            if ($existingUser) {
                // Replicate existing user to new company
                $user = $existingUser->replicate();
                $user->password = null;
                $user->company_id = $companyId;
                $user->is_owner = $isOwner;
                $user->management_hierarchy_id = $role == CompanyUserRole::EMPLOYEE->value ? $mainBranchData['managementId'] : null;
                $user->save();
                $user = $user->fresh();
            } else {
                // Create totally new user
                $user = $this->userRepository->createUser([
                    'name' => $name,
                    'email' => $companyUser->email,
                    'company_id' => $companyId,
                    'phone' => $companyUser->phone,
                    'phone_code' => $companyUser->phone_code,
                    'global_company_user_id' => $companyUser->global_id,
                    'is_owner' => $isOwner,
                    'management_hierarchy_id' => $role == CompanyUserRole::EMPLOYEE->value ? $mainBranchData['managementId'] : null,
                ]);
            }
        } elseif ($user->deleted_at !== null) {
            // Restore if necessary
            $user->restore();
            $user = $user->fresh();
        } else {
            $usersInCompanyCount = $this->companyRepository->findOneBy(["id" => $companyId])->users()->where("is_owner", 1)->count();
            $isOwner = $usersInCompanyCount === 0 ? 1 : 0;
            $user->update([
                'name' => $name,
                'email' => $companyUser->email,
                'company_id' => $companyId,
//                'phone' => $companyUser->phone,
//                'phone_code' => $companyUser->phone_code,
                'global_company_user_id' => $companyUser->global_id,
                'is_owner' => $isOwner,
                'management_hierarchy_id' => $role == CompanyUserRole::EMPLOYEE->value ? $mainBranchData['managementId'] : null,
            ]);
            $user = $user->fresh();
        }

        return $user;
    }

    /**
     * Handle permissions for company owner
     */
    private function handleOwnerPermissions(User $user, $companyId): void
    {
        if ($user->is_owner) {
            $branch = $this->managementHierarchyRepository->model->withoutTenancy()->where([
                "company_id" => $companyId,
                "parent_id" => null
            ])->first();

            $role = Role::query()->withoutTenancy()->where("name", "super-admin")->where("company_id", $companyId)->first();
            setPermissionsTeamId($companyId);
            $user->assignRole($role);//assign super admin role for first user

            $this->userCanAccessManagementHierarchyRepository->assignUsersToManagementHierarchy(new AssignUsersToManagementHierarchyDTO(branchId: $branch->id, userIds: [$user->id]));


            $branch->update(["manager_id" => $user->id]);

            $this->managementHierarchyRepository->model->withoutTenancy()->where([
                "company_id" => $companyId,
                "parent_id" => $branch->id,
                "type" => "management",
                "is_main" => 1
            ])->first()->update(["manager_id" => $user->id]);
        }
    }

    /**
     * Create or update company user role
     */
    private function createOrUpdateCompanyUserRole(CompanyUser $companyUser, array $companyRole): CompanyUserCompany
    {
        $companyUserCompany = $this->companyUserCompanyRepository->findOneBy([
            "role" => $companyRole['role'],
            "global_company_user_id" => $companyUser->global_id,
            'company_id' => $companyRole['company_id']
        ]);

        if (!$companyUserCompany) {
            $companyUserCompany = $this->companyUserCompanyRepository->createOrRestore(
                $companyRole + ["global_company_user_id" => $companyUser->id]
            );
        } elseif ($companyUserCompany->deleted_at !== null) {
            $companyUserCompany->restore();
        }

        return $companyUserCompany;
    }

    /**
     * Handle branch assignments for company user
     */
    private function handleBranchAssignments(User $user, CompanyUserCompany $companyUserCompany, array $companyRole, ?array $branches): mixed

    {
        // Remove existing associations
        $this->companyUserManagementHierarchyRepository->deleteWhere(["company_user_company_id" => $companyUserCompany->id]);

        $mainBranchData = $this->getMainBranchData($companyRole['company_id'], $branches);

        if ($branches !== null) {
            // Create multi-branch associations
            $this->createMultiBranchAssociations($user, $companyUserCompany, $branches);
        } elseif (CompanyUserRole::EMPLOYEE->value == $companyRole['role']) {
            // Create single branch association for employee
            $this->createBranchAssociation($user, $companyUserCompany, $mainBranchData['branchId']);
        }

        return $mainBranchData['branchId'];
    }

    /**
     * Create branch association
     */
    private function createBranchAssociation(User $user, CompanyUserCompany $companyUserCompany, $branchId): void
    {
        $this->companyUserManagementHierarchyRepository->updateOrCreate(
            [
                "user_id" => $user->id,
                "management_hierarchy_id" => $branchId,
                "company_user_company_id" => $companyUserCompany->id
            ],
            [
                "user_id" => $user->id,
                "management_hierarchy_id" => $branchId,
                "company_user_company_id" => $companyUserCompany->id
            ]
        );
    }

    /**
     * Create multiple branch associations
     */
    private function createMultiBranchAssociations(User $user, CompanyUserCompany $companyUserCompany, array $branches): void
    {
        foreach ($branches as $branch) {
            $this->createBranchAssociation($user, $companyUserCompany, $branch);
        }
    }

    /**
     * Get main branch and management data
     */
    private function getMainBranchData($companyId, ?array $branches = null): array
    {
        $mainBranchId = $this->managementHierarchyRepository->model->withoutTenancy()->where([
            "company_id" => $companyId,
            "parent_id" => null
        ])  ->first()->id;

        $branchId = $mainBranchId;

        if ($branches !== null && !empty($branches)) {
            $branchId = $branches[0];
        }

        $mainManagement = $this->managementHierarchyRepository->model->withoutTenancy()->where([
            "company_id" => $companyId,
            "parent_id" => $branchId,
            "is_main" => 1,
            "type" => "management"
        ])->first();

        $managementId = $mainManagement ? $mainManagement->id : null;

        return [
            'branchId' => $branchId,
            'managementId' => $managementId
        ];
    }

    /**
     * Handle employee professional data
     */
    private function handleEmployeeData(User $user, $companyId, int $branchId, array $companyUserData = []): void
    {
        $generalManagerJobTitle = $this->jobTitleRepository->model->withoutTenancy()->where(["type" => "general_manager", "company_id" => $companyId])->first();
        if (isset($companyUserData["job_title_id"]) && $companyUserData["job_title_id"] && $companyUserData["job_title_id"] != null) {
            $companyIdJobTitle = $this->jobTitleRepository->model->withoutTenancy()->where(["id" => $companyUserData["job_title_id"]])->first()->company_id;
            if ($companyId != $companyIdJobTitle) {
                $companyUserData["job_title_id"] = $generalManagerJobTitle->id;
            }
        }


        // Get management hierarchy
        $mainManagement = $this->managementHierarchyRepository->model->withoutTenancy()->where([
            "company_id" => $companyId,
            "parent_id" => $branchId,
            "type" => "management",
            "is_main" => 1
        ])->first();

        $jobTitleId = $companyUserData["job_title_id"] ?? $generalManagerJobTitle->id;
        $jobTypeId = isset($companyUserData["job_title_id"]) && $companyUserData["job_title_id"] !== null
            ? $this->jobTitleRepository->model->withoutTenancy()->where(["type" => "general_manager", "company_id" => $companyId])->first()->job_type_id
            : $generalManagerJobTitle->job_type_id;

        // $attendanceConstraint = $this->attendanceConstraintRepository->model->getConstraintBybranch($branchId);
        $attendanceConstraint = AttendanceConstraint::withoutTenancy()
            ->whereJsonContains('branch_ids', (string)$branchId)
            ->first();
        if (!$attendanceConstraint) {
            $attendanceConstraint = AttendanceConstraint::where('company_id', $companyId)->withoutTenancy()->first();
        }

        $data = [
            'company_id' => $companyId,
            'global_id' => $user->global_company_user_id,
            'branch_id' => $branchId,
            'management_id' => $mainManagement->id ?? null,
            'job_title_id' => $jobTitleId,
            'job_type_id' => $jobTypeId,
            "user_id" => $user->id,
            'attendance_constraint_id' => $attendanceConstraint->id ?? null,
        ];

        // Create or update professional data
        $userProfessionalData = $this->userProfessionalDataRepository->model->withoutTenancy()->where([
            'global_id' => $user->global_company_user_id,
            'company_id' => $companyId,
        ])->first();

        if ($userProfessionalData) {
            $userProfessionalData->update($data);
            $professionalData = $userProfessionalData->refresh();
        } else {
            $professionalData = UserProfessionalData::create($data);
        }

        if ($professionalData && $professionalData->attendance_constraint_id) {
            $this->autoAttendanceService->generateAttendanceUsers($companyId, $user->id);
        }
    }

    public function setAddress(array $addressData)
    {
        return $this->companyUserAddressRepository->create($addressData);
    }

    public function getUserInBranches($globalId, $role, array $branchIds)
    {
        return $this->companyUserManagementHierarchyRepository->getUserInBranches($globalId, $role, $branchIds);

    }

    public
    function updateCompanyUser(UuidInterface $id, array $data): bool
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
        $user = $this->userRepository->findOneBy(["id" => $userId]);


        if(isset($data["email"]))
        {
            $this->userRepository->model->withoutTenancy()->where(["email"=>$user->email])->update(
                ["email"=>$data["email"]]
            );
            $this->model->where(["id" =>$user->global_company_user_id ])->first()->update( ["email" => $data["email"]]);
        }

        if(isset($data["phone"]))
        {
            $this->userRepository->model->withoutTenancy()->where(["phone"=>$user->phone])->update(
                ["phone"=>$data["phone"]]
            );
        }

        return true;
    }

    public function canDelete($companyUser = null)
    {
        if (!$companyUser) {
            throw new CustomException(__("validation.company_user_not_found"), 400);
        }

        // Check if trying to delete admin account
        if ($companyUser->email === 'admin@constrix-nv.com') {
            throw new CustomException(__("validation.admin_account_cannot_be_deleted"), 400);
        }

        // Check if trying to delete self
        $currentUserId = auth()->user()->global_company_user_id ?? null;
        if ($currentUserId && $currentUserId === $companyUser->global_id) {
            throw new CustomException(__("validation.cannot_delete_yourself"), 400);
        }

        // Check if trying to delete company owner
        $isOwner = \Modules\User\Models\User::where('global_company_user_id', $companyUser->global_id)
            ->where('is_owner', true)
            ->exists();

        if ($isOwner) {
            throw new CustomException(__("validation.cannot_delete_company_owner"), 400);
        }

        return true;

    }

    public
    function deleteCompanyUser(UuidInterface $id): bool
    {
        try {
            DB::beginTransaction();
            $companyUser = $this->findOneBy(["id" => $id]);

            $this->canDelete($companyUser);

            $this->companyUserCompanyRepository->deleteWhere(["global_company_user_id" => $companyUser->global_id]);
            $this->delete($id);
            DB::commit();

        } catch (CustomException $e) {
            DB::rollBack();
            throw $e;
        }
        return true;
    }

    public function getIdsWithRelations($ids = [], $relations = [])
    {
        return $this->model->with($relations)->whereIn("id", $ids)->get();
    }

    public
    function getAllWithRelations($relations = [])
    {
        return $this->model->with($relations)->get();
    }

    /**
     * Update the status of a user role in company_user_company table
     *
     * @param string $userId
     * @param string $roleId
     * @param int $status
     * @return CompanyUserCompany
     * @throws CustomException
     */
    public function updateUserRoleStatus(string $userId, $roleId, int $status): CompanyUserCompany
    {
        // Find the CompanyUserCompany record based on user_id and role_id
        $companyUserCompany = CompanyUserCompany::where('role', $roleId)
            ->whereHas('companyUser', function ($query) use ($userId) {
                $query->whereHas('users', function ($subQuery) use ($userId) {
                    $subQuery->where('id', $userId);
                });
            })
            ->first();

        if (!$companyUserCompany) {
            throw new CustomException('User role not found or user does not have access to this role', 404);
        }

        // Update the status
        $companyUserCompany->status = (string)$status;
        $companyUserCompany->save();

        return $companyUserCompany->refresh();
    }

    /**
     * Get company users for export with specific role
     *
     * @param array $filters
     * @param int $role
     * @return \Illuminate\Support\Collection
     */
    public function getForExport(array $filters = [], int $role = null): \Illuminate\Support\Collection
    {
        $query = $this->model->newQuery()
            ->with(['users', 'companies'])
            ->whereHas('users', fn($q) => $q->where('company_id', tenant('id')));

        // Filter by role if specified
        if ($role !== null) {
            $query->whereHas('users.companyUserCompanies', function ($q) use ($role) {
                $q->where('company_id', tenant('id'))
                    ->where('role', $role);
            });
        }

        // Filter by specific IDs if provided
        if (!empty($filters['ids'])) {
            $query->whereIn('id', $filters['ids']);
        }

        return $query->get();
    }

    private function companyUsersQuery()
    {
        return $this->model->whereHas('users', fn($q) => $q->where('company_id', tenant('id')));
    }

    public function getGenderDistribution(): array
    {
        $query = $this->companyUsersQuery();
        $total = $query->count();

        if ($total === 0) {
            return [
                'total' => 0,
                'male' => ['count' => 0, 'percentage' => 0],
                'female' => ['count' => 0, 'percentage' => 0],
                'unspecified' => ['count' => 0, 'percentage' => 0],
            ];
        }

        $maleCount = (clone $query)->where('gender', 'male')->count();
        $femaleCount = (clone $query)->where('gender', 'female')->count();
        $unspecifiedCount = $total - $maleCount - $femaleCount;

        return [
            'total' => $total,
            'male' => [
                'count' => $maleCount,
                'percentage' => round(($maleCount / $total) * 100, 2),
            ],
            'female' => [
                'count' => $femaleCount,
                'percentage' => round(($femaleCount / $total) * 100, 2),
            ],
            'unspecified' => [
                'count' => $unspecifiedCount,
                'percentage' => round(($unspecifiedCount / $total) * 100, 2),
            ],
        ];
    }

    public function getAgeDistribution(): array
    {
        $query = $this->companyUsersQuery();
        $totalUsers = $query->count();

        $users = (clone $query)->whereNotNull('birthdate_gregorian')
            ->where('birthdate_gregorian', '!=', '')
            ->pluck('birthdate_gregorian');

        $totalWithBirthdate = $users->count();
        $unspecifiedCount = $totalUsers - $totalWithBirthdate;

        $ranges = [
            '10-19' => [10, 19],
            '20-29' => [20, 29],
            '30-39' => [30, 39],
            '40-49' => [40, 49],
            '50-59' => [50, 59],
            '60-69' => [60, 69],
            '70-79' => [70, 79],
            '80-89' => [80, 89],
            '90-99' => [90, 99],
            '100+' => [100, null],
        ];

        $distribution = [];
        foreach ($ranges as $key => $range) {
            $distribution[$key] = 0;
        }

        foreach ($users as $birthdate) {
            try {
                $age = Carbon::parse($birthdate)->age;
            } catch (\Exception $e) {
                $unspecifiedCount++;
                $totalWithBirthdate--;
                continue;
            }

            foreach ($ranges as $key => $range) {
                if ($range[1] === null && $age >= $range[0]) {
                    $distribution[$key]++;
                    break;
                } elseif ($age >= $range[0] && $age <= $range[1]) {
                    $distribution[$key]++;
                    break;
                }
            }
        }

        $result = [
            'total' => $totalUsers,
            'ranges' => [],
        ];

        foreach ($distribution as $key => $count) {
            $result['ranges'][$key] = [
                'count' => $count,
                'percentage' => $totalUsers > 0 ? round(($count / $totalUsers) * 100, 2) : 0,
            ];
        }

        $result['ranges']['unspecified'] = [
            'count' => $unspecifiedCount,
            'percentage' => $totalUsers > 0 ? round(($unspecifiedCount / $totalUsers) * 100, 2) : 0,
        ];

        return $result;
    }

    public function getJobTypeDistribution(): array
    {
        $companyId = tenant('id');

        $professionalData = UserProfessionalData::where('company_id', $companyId)->get();
        $total = $professionalData->count();

        if ($total === 0) {
            return [
                'total' => 0,
                'data' => [
                    [
                        'job_type_id' => null,
                        'name' => __('غير محدد'),
                        'code' => 'unspecified',
                        'count' => 0,
                        'percentage' => 0,
                    ],
                ],
            ];
        }

        $withJobType = $professionalData->whereNotNull('job_type_id');
        $unspecifiedCount = $total - $withJobType->count();

        $grouped = $withJobType->groupBy('job_type_id');

        $data = [];
        foreach ($grouped as $jobTypeId => $items) {
            $jobType = \Modules\Shared\JobType\Models\JobType::withoutGlobalScope('active')->find($jobTypeId);
            $count = $items->count();
            $data[] = [
                'job_type_id' => $jobTypeId,
                'name' => $jobType ? $jobType->name : null,
                'count' => $count,
                'percentage' => round(($count / $total) * 100, 2),
            ];
        }

        usort($data, fn($a, $b) => $b['count'] <=> $a['count']);

        $data[] = [
            'job_type_id' => null,
            'name' => __('غير محدد'),
            'code' => 'unspecified',
            'count' => $unspecifiedCount,
            'percentage' => round(($unspecifiedCount / $total) * 100, 2),
        ];

        return [
            'total' => $total,
            'data' => $data,
        ];
    }

    public function getVisaExpirationByMonth(): array
    {
        $query = $this->companyUsersQuery();
        $totalUsers = $query->count();

        $usersWithVisa = (clone $query)->whereNotNull('entry_number_end_date')
            ->where('entry_number_end_date', '!=', '')
            ->get(['entry_number_end_date']);

        $totalWithVisa = $usersWithVisa->count();
        $noVisaCount = $totalUsers - $totalWithVisa;

        $currentYear = Carbon::now()->year;
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $date = Carbon::createFromDate($currentYear, $m, 1);
            $key = $date->format('Y-m');
            $months[$key] = ['label' => $date->translatedFormat('F Y'), 'count' => 0];
        }

        foreach ($usersWithVisa as $user) {
            try {
                $date = Carbon::parse($user->entry_number_end_date);
                $key = $date->format('Y-m');
                $label = $date->translatedFormat('F Y');
            } catch (\Exception $e) {
                $noVisaCount++;
                continue;
            }

            if (!isset($months[$key])) {
                $months[$key] = ['label' => $label, 'count' => 0];
            }
            $months[$key]['count']++;
        }

        ksort($months);

        $data = [];
        foreach ($months as $key => $item) {
            $data[] = [
                'month' => $key,
                'label' => $item['label'],
                'count' => $item['count'],
                'percentage' => $totalWithVisa > 0 ? round(($item['count'] / $totalWithVisa) * 100, 2) : 0,
            ];
        }

        $data[] = [
            'month' => null,
            'label' => __('بدون تأشيرة'),
            'code' => 'no_visa',
            'count' => $noVisaCount,
            'percentage' => $totalUsers > 0 ? round(($noVisaCount / $totalUsers) * 100, 2) : 0,
        ];

        return [
            'total' => $totalWithVisa,
            'data' => $data,
        ];
    }

    public function getVisaStatusDistribution(): array
    {
        $query = $this->companyUsersQuery();
        $totalUsers = $query->count();

        $withVisa = (clone $query)->whereNotNull('entry_number_end_date')
            ->where('entry_number_end_date', '!=', '');

        $totalWithVisa = $withVisa->count();
        $noVisaCount = $totalUsers - $totalWithVisa;

        $today = Carbon::today();
        $expiredCount = (clone $withVisa)->where('entry_number_end_date', '<', $today)->count();
        $validCount = $totalWithVisa - $expiredCount;

        return [
            'total' => $totalUsers,
            'expired' => [
                'count' => $expiredCount,
                'percentage' => $totalUsers > 0 ? round(($expiredCount / $totalUsers) * 100, 2) : 0,
            ],
            'valid' => [
                'count' => $validCount,
                'percentage' => $totalUsers > 0 ? round(($validCount / $totalUsers) * 100, 2) : 0,
            ],
            'no_visa' => [
                'count' => $noVisaCount,
                'percentage' => $totalUsers > 0 ? round(($noVisaCount / $totalUsers) * 100, 2) : 0,
            ],
        ];
    }

    private function calculateContractEndDate($contract): ?Carbon
    {
        if (!$contract->start_date || !$contract->contract_duration) {
            return null;
        }

        try {
            $startDate = Carbon::parse($contract->start_date);
            $duration = (int) $contract->contract_duration;
            $unitCode = $contract->contractDurationUnit?->code ?? 'month';

            return match ($unitCode) {
                'day' => $startDate->addDays($duration),
                'month' => $startDate->addMonths($duration),
                'year' => $startDate->addYears($duration),
                default => $startDate->addMonths($duration),
            };
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getContractExpirationByMonth(): array
    {
        $companyId = tenant('id');
        $query = $this->companyUsersQuery();
        $totalUsers = $query->count();

        $globalIds = (clone $query)->pluck('global_id');

        $contracts = \Modules\UserInfo\EmploymentContract\Models\EmploymentContract::where('company_id', $companyId)
            ->whereIn('global_id', $globalIds)
            ->whereNotNull('start_date')
            ->whereNotNull('contract_duration')
            ->with('contractDurationUnit')
            ->get();

        $totalWithContract = $contracts->count();
        $noContractCount = $totalUsers - $totalWithContract;

        $currentYear = Carbon::now()->year;
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $date = Carbon::createFromDate($currentYear, $m, 1);
            $key = $date->format('Y-m');
            $months[$key] = ['label' => $date->translatedFormat('F Y'), 'count' => 0];
        }

        $validContracts = 0;

        foreach ($contracts as $contract) {
            $endDate = $this->calculateContractEndDate($contract);
            if (!$endDate) {
                $noContractCount++;
                continue;
            }

            $validContracts++;
            $key = $endDate->format('Y-m');
            $label = $endDate->translatedFormat('F Y');

            if (!isset($months[$key])) {
                $months[$key] = ['label' => $label, 'count' => 0];
            }
            $months[$key]['count']++;
        }

        ksort($months);

        $data = [];
        foreach ($months as $key => $item) {
            $data[] = [
                'month' => $key,
                'label' => $item['label'],
                'count' => $item['count'],
                'percentage' => $validContracts > 0 ? round(($item['count'] / $validContracts) * 100, 2) : 0,
            ];
        }

        $data[] = [
            'month' => null,
            'label' => __('بدون عقد'),
            'code' => 'no_contract',
            'count' => $noContractCount,
            'percentage' => $totalUsers > 0 ? round(($noContractCount / $totalUsers) * 100, 2) : 0,
        ];

        return [
            'total' => $validContracts,
            'data' => $data,
        ];
    }

    public function getContractStatusDistribution(): array
    {
        $companyId = tenant('id');
        $query = $this->companyUsersQuery();
        $totalUsers = $query->count();

        $globalIds = (clone $query)->pluck('global_id');

        $contracts = \Modules\UserInfo\EmploymentContract\Models\EmploymentContract::where('company_id', $companyId)
            ->whereIn('global_id', $globalIds)
            ->with('contractDurationUnit')
            ->get();

        $noContractCount = $totalUsers - $contracts->count();
        $today = Carbon::today();
        $expiredCount = 0;
        $validCount = 0;

        foreach ($contracts as $contract) {
            $endDate = $this->calculateContractEndDate($contract);
            if (!$endDate) {
                $noContractCount++;
                continue;
            }

            if ($endDate->lt($today)) {
                $expiredCount++;
            } else {
                $validCount++;
            }
        }

        return [
            'total' => $totalUsers,
            'expired' => [
                'count' => $expiredCount,
                'percentage' => $totalUsers > 0 ? round(($expiredCount / $totalUsers) * 100, 2) : 0,
            ],
            'valid' => [
                'count' => $validCount,
                'percentage' => $totalUsers > 0 ? round(($validCount / $totalUsers) * 100, 2) : 0,
            ],
            'no_contract' => [
                'count' => $noContractCount,
                'percentage' => $totalUsers > 0 ? round(($noContractCount / $totalUsers) * 100, 2) : 0,
            ],
        ];
    }

    public function getNationalityDistribution(): array
    {
        $query = $this->companyUsersQuery();
        $total = $query->count();

        if ($total === 0) {
            return [
                'total' => 0,
                'data' => [
                    [
                        'country_id' => null,
                        'name' => __('غير محدد'),
                        'code' => 'unspecified',
                        'count' => 0,
                        'percentage' => 0,
                    ],
                ],
            ];
        }

        $withCountry = (clone $query)->whereNotNull('country_id')
            ->where('country_id', '!=', '')
            ->get(['country_id']);

        $unspecifiedCount = $total - $withCountry->count();
        $grouped = $withCountry->groupBy('country_id');

        $data = [];
        foreach ($grouped as $countryId => $items) {
            $country = \Modules\Country\Models\Country::find($countryId);
            $count = $items->count();
            $data[] = [
                'country_id' => $countryId,
                'name' => $country ? $country->name : null,
                'count' => $count,
                'percentage' => round(($count / $total) * 100, 2),
            ];
        }

        usort($data, fn($a, $b) => $b['count'] <=> $a['count']);

        $data[] = [
            'country_id' => null,
            'name' => __('غير محدد'),
            'code' => 'unspecified',
            'count' => $unspecifiedCount,
            'percentage' => round(($unspecifiedCount / $total) * 100, 2),
        ];

        return [
            'total' => $total,
            'data' => $data,
        ];
    }

    public function getMaritalStatusDistribution(): array
    {
        $companyId = tenant('id');

        $allStatuses = \Modules\Shared\MaritalStatus\Models\MaritalStatus::all();

        $relatives = \Modules\UserInfo\UserRelative\Models\UserRelative::where('company_id', $companyId)
            ->whereNotNull('marital_status_id')
            ->where('marital_status_id', '!=', '')
            ->get(['marital_status_id']);

        $grouped = $relatives->groupBy('marital_status_id');
        $totalRelatives = $relatives->count();

        $data = [];
        foreach ($allStatuses as $status) {
            $count = isset($grouped[$status->id]) ? $grouped[$status->id]->count() : 0;
            $data[] = [
                'marital_status_id' => $status->id,
                'name' => $status->name,
                'count' => $count,
                'percentage' => $totalRelatives > 0 ? round(($count / $totalRelatives) * 100, 2) : 0,
            ];
        }

        return [
            'total' => $totalRelatives,
            'data' => $data,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Repositories;

use App\Exceptions\CustomException;
use BasePackage\Shared\Repositories\BaseRepository;
use Carbon\Carbon;
use Composer\Autoload\ClassLoader;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Repositories\AttendanceConstraintRepository;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Enum\CompanyUserStatus;
use Modules\CompanyUser\Models\ClientDetail;
use Modules\CompanyUser\Models\CompanyUserAddress;
use Modules\CompanyUser\Models\CompanyUserCompany;
use Modules\CompanyUser\Models\CompanyUserCompanyManagementHierarchy;
use Modules\JobTitle\Models\JobTitle;
use Modules\JobTitle\Repositories\JobTitleRepository;
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
        CompanyUser $model,
        private UserRepository $userRepository,
        private JobTitleRepository $jobTitleRepository,
        private CompanyRepository $companyRepository,
        private ManagementHierarchyRepository $managementHierarchyRepository,
        private UserProfessionalDataRepository $userProfessionalDataRepository,
        private CompanyUserCompanyRepository $companyUserCompanyRepository,
        private CompanyUserAddressRepository $companyUserAddressRepository,
        private ClientDetailRepository $clientDetailRepository,
        private CompanyUserManagementHierarchyRepository $companyUserManagementHierarchyRepository,
        private AttendanceConstraintRepository $attendanceConstraintRepository
    ) {
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


    public function createCompanyUser(array $companyUserData, array $companyRole, array $branches = null, array $address = null, array $clientDetail = null): CompanyUser
    {
        try {
            $phone = $this->getPhoneNumberInfo($companyUserData['phone']);

            DB::beginTransaction();
            $generalManagerJobTitle = $this->jobTitleRepository->model->withoutTenancy()->where(["type" => "general_manager","company_id"=>$companyRole['company_id']])->first();
            if(isset($companyUserData["job_title_id"])&&$companyUserData["job_title_id"] && $companyUserData["job_title_id"] != null)
            {
                $companyIdJobTitle = $this->jobTitleRepository->model->withoutTenancy()->where(["id" =>$companyUserData["job_title_id"]])->first()->company_id;
                if($companyRole['company_id'] != $companyIdJobTitle )
                {
                    $companyUserData["job_title_id"] = $generalManagerJobTitle->id;
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
                $companyUserData['name'] ,
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

            // Handle address if provided
            if ($address !== null) {
                $this->companyUserAddressRepository->updateOrCreate(
                    ["global_company_user_id" => $companyUser->id],
                    $address + ["global_company_user_id" => $companyUser->id]
                );
            }

            // Handle client details if client role
            if (CompanyUserRole::CLIENT->value == $companyRole['role'] && $clientDetail !== null) {
                $this->clientDetailRepository->updateOrCreate(
                    ["user_id" => $user->id],
                    $clientDetail + ["user_id" => $user->id]
                );
            }

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
        }elseif ($companyUser->deleted_at !== null) {

            $companyUser->restore();
        }
        $companyUser->update(["global_id" => $companyUser->id]);

        return $companyUser->fresh();
    }

    /**
     * Find or create user within a company
     */
    private function findOrCreateUserInCompany(CompanyUser $companyUser, $companyId, string $name,  $role, ?array $branches = null): User
    {
        // Try to find existing user in company
        $user = $this->userRepository->findOneBy([
            "global_company_user_id" => $companyUser->global_id,
            "company_id" => $companyId
        ]);

        if (!$user) {
            // Get main branch data
            $mainBranchData = $this->getMainBranchData($companyId, $branches);

            // Try to find user in any company
            $existingUser = $this->userRepository->findOneBy([
                "global_company_user_id" => $companyUser->global_id
            ]);

            $usersInCompanyCount = $this->companyRepository->findOneBy(["id" => $companyId])->users()->where("is_owner",1)->count();
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
        } else {
            // Restore if necessary
            $user->restore();
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
            $branch = $this->managementHierarchyRepository->findOneBy([
                "company_id" => $companyId,
                "parent_id" => null
            ]);
            $user->assignRole('super-admin');//assign super admin role for first user


            $branch->update(["manager_id" => $user->id]);

            $this->managementHierarchyRepository->findOneBy([
                "company_id" => $companyId,
                "parent_id" => $branch->id,
                "type" => "management",
                "is_main" => 1
            ])->update(["manager_id" => $user->id]);
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
    private function handleBranchAssignments(User $user, CompanyUserCompany $companyUserCompany, array $companyRole, ?array $branches):mixed

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
    private function createBranchAssociation(User $user, CompanyUserCompany $companyUserCompany,  $branchId): void
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
        $mainBranchId = $this->managementHierarchyRepository->findOneBy([
            "company_id" => $companyId,
            "parent_id" => null
        ])->id;

        $branchId = $mainBranchId;

        if ($branches !== null && !empty($branches)) {
            $branchId = $branches[0];
        }

        $mainManagement = $this->managementHierarchyRepository->findOneBy([
            "company_id" => $companyId,
            "parent_id" => $branchId,
            "is_main" => 1,
            "type" => "management"
        ]);

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
        $generalManagerJobTitle = $this->jobTitleRepository->model->withoutTenancy()->where(["type" => "general_manager","company_id"=>$companyId])->first();
        if(isset($companyUserData["job_title_id"])&&$companyUserData["job_title_id"] && $companyUserData["job_title_id"] != null)
        {
            $companyIdJobTitle = $this->jobTitleRepository->model->withoutTenancy()->where(["id" =>$companyUserData["job_title_id"]])->first()->company_id;
            if($companyId != $companyIdJobTitle )
            {
                $companyUserData["job_title_id"] = $generalManagerJobTitle->id;
            }
        }


        // Get management hierarchy
        $mainManagement = $this->managementHierarchyRepository->findOneBy([
            "company_id" => $companyId,
            "parent_id" => $branchId,
            "type" => "management",
            "is_main" => 1
        ]);

        $jobTitleId = $companyUserData["job_title_id"] ?? $generalManagerJobTitle->id;
        $jobTypeId = isset($companyUserData["job_title_id"]) && $companyUserData["job_title_id"] !== null
            ? $this->jobTitleRepository->model->withoutTenancy()->where(["type" => "general_manager","company_id"=>$companyId])->first()->job_type_id
            : $generalManagerJobTitle->job_type_id;

        $attendanceConstraint = $this->attendanceConstraintRepository->getConstraintBybranch($branchId);
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
        } else {
            UserProfessionalData::create($data);
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
        $this->userRepository->updateWhere(
            ["id" => $userId], $data
        );

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
}

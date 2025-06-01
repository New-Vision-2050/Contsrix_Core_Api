<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Illuminate\Support\Facades\Validator;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchySimpleDataPresenter;
use Modules\CompanyUser\DTO\CreateCompanyUserCompanyRoleDTO;
use Modules\CompanyUser\DTO\CreateCompanyUserDTO;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\User\Presenters\UserBranchesPresenter;
use Modules\User\Repositories\UserRepository;
use Ramsey\Uuid\UuidInterface;

class CompanyUserValidationService
{

    private $errors = [];

    public function __construct(
        private CompanyUserRepository $repository,
        private UserRepository        $userRepository,
    )
    {

    }

    public function validateName()
    {
        if (!preg_match("/\p{Arabic}/u", request()->name) || count(explode(" ", request()->name)) < 3) {
            $this->errors[] = [
                'sentence' => __("validation.user-name"),
                'sub_title' => '',
                'status' => 0,
                'validate' => 'required'
            ];
        } else {
            $this->errors[] = [
                'sentence' => __("validation.user-name"),
                'sub_title' => '',
                'status' => 1,
                'validate' => 'required'
            ];
        }
        return $this;
    }

    private function getRolesAndPermissions($companyUserCompany)
    {

        $rolesWithBranches = [];

        foreach (CompanyUserRole::values() as $availableRole) {
            $branches = []; // Initialize branches as an empty array for each role
            foreach ($companyUserCompany as $roleWithBranches) {
                if ($roleWithBranches->getRawOriginal("role") == $availableRole) {
                    $branches = $roleWithBranches->managementHierarchy ? ManagementHierarchySimpleDataPresenter::collection($roleWithBranches->managementHierarchy) : [];
                }
            }
            if ($branches == null) {
                $branches = [];
            }
            $rolesWithBranches[] = [
                'role' => $availableRole,
                'branches' => $branches
            ];
        }
        return $rolesWithBranches;

    }

    public function validateEmail()
    {
        if ($user = $this->repository->findByEmail(request()->email)) {
            $userInCompany = $this->userRepository->findOneBy(["email" => request()->email, "company_id" => tenant("id")]);
            $role = request()->has("role") ? request()->role : 1;
            $companyUserCompany = $this->userRepository->getUserByGlobalIdWithBranches($userInCompany?->global_company_user_id, $role);

            $this->errors[] = [
                'sentence' => __("validation.user-email-error", ["name" => $user->name]),
                'sub_title' => 'email',
                'status' => 0,
                "status_in_company" => $userInCompany == null ? 0 : 1,
                "roles" => $this->getRolesAndPermissions($companyUserCompany),
                'validate' => 'required',
                'id' => $user->id,
                "email"=>$user->email
            ];
        } else {
            $this->errors[] = [
                'sentence' => __("validation.user-email-success"),
                'sub_title' => 'email',
                'status' => 1,
                "branches" => [],
                "status_in_company" => 0,
                'validate' => 'required'
            ];
        }
        return $this;
    }

    public function validatePhone()
    {
        $validator = Validator::make(request()->all(), ['phone' => 'required|phone']);
        if ($this->repository->findByPhone(request()->phone)) {
            $this->errors[] = [
                'sentence' => __("validation.user-phone-error"),
                'sub_title' => 'phone',
                'status' => 0,
                'validate' => 'required'
            ];
        } else if ($validator->fails()) {
            $this->errors[] = [
                'sentence' => $validator->errors()->messages()["phone"][0],
                'sub_title' => 'phone',
                'status' => 0,
                'validate' => 'required'
            ];
        } else {
            $this->errors[] = [
                'sentence' => __("validation.user-phone-success"),
                'sub_title' => 'phone',
                'status' => 1,
                'validate' => 'required'
            ];
        }
        return $this;
    }

    public function get()
    {
        return $this->errors;
    }


}

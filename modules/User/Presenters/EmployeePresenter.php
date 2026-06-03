<?php

declare(strict_types=1);

namespace Modules\User\Presenters;

use Modules\User\Models\User;
use Modules\JobTitle\Models\JobTitle;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Country\Models\Country;
use Modules\Shared\Privilege\Services\PrivilegeCardConfigService;
use Modules\Shared\TypePrivilege\Models\TypePrivilege;
use Modules\UserInfo\UserPrivilege\Filters\UserPrivilegeFilter;
use Modules\UserInfo\UserPrivilege\Models\UserPrivilege;

class EmployeePresenter extends AbstractPresenter
{
    private const HEALTH_INSURANCE_ALLOWANCE_CODES = ['constant', 'saving'];

    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            'job_title' => $this->formatJobTitle($this->user->companyUser?->jobTitle),
            'country' => $this->formatCountry($this->user->companyUser?->country),
            'status' => $this->user->status,
            'branch' => $this->formatBranch($this->user?->managementHierarchy?->detail?->branch),
            'type_privilege' => $this->presentHealthInsuranceTypePrivilege(),
        ];
    }

    private function presentHealthInsuranceTypePrivilege(): ?string
    {
        if (! $this->user->relationLoaded('userPrivileges')) {
            return null;
        }

        $allowanceCode = $this->requestedAllowanceCode();

        $userPrivilege = $this->user->userPrivileges
            ->filter(fn (UserPrivilege $userPrivilege) => $this->isHealthInsurancePrivilege($userPrivilege))
            ->when(
                $allowanceCode !== null,
                fn ($privileges) => $privileges->where('type_allowance_code', $allowanceCode),
                fn ($privileges) => $privileges
                    ->filter(fn (UserPrivilege $userPrivilege) => in_array(
                        $userPrivilege->type_allowance_code,
                        self::HEALTH_INSURANCE_ALLOWANCE_CODES,
                        true
                    ))
                    ->sortByDesc(fn (UserPrivilege $userPrivilege) => $userPrivilege->type_allowance_code === 'saving')
            )
            ->first();

        if ($userPrivilege === null) {
            return null;
        }

        return $this->healthInsuranceTypePrivilegeLabel($userPrivilege->typePrivilege);
    }

    private function requestedAllowanceCode(): ?string
    {
        $code = request()->input('type_allowance_code');

        return in_array($code, self::HEALTH_INSURANCE_ALLOWANCE_CODES, true) ? $code : null;
    }

    private function isHealthInsurancePrivilege(UserPrivilege $userPrivilege): bool
    {
        if ($userPrivilege->medical_insurance_id !== null) {
            return true;
        }

        return $userPrivilege->privilege?->type === PrivilegeCardConfigService::TYPE_HEALTH_INSURANCE;
    }

    private function healthInsuranceTypePrivilegeLabel(?TypePrivilege $typePrivilege): ?string
    {
        if ($typePrivilege === null) {
            return null;
        }

        $englishName = $typePrivilege->getTranslation('name', 'en');

        return match (strtolower($englishName)) {
            'individual' => UserPrivilegeFilter::TYPE_PRIVILEGE_INDIVIDUAL,
            'family' => UserPrivilegeFilter::TYPE_PRIVILEGE_FAMILY,
            default => $englishName !== '' ? $englishName : null,
        };
    }

    protected function formatJobTitle(?JobTitle $jobTitle)
    {
        if (blank($jobTitle)) {
            return [];
        }

        return [
            'id' => $jobTitle->id,
            'name' => $jobTitle->translations?->where('locale', app()->getLocale())->first()->content
        ];
    }

    protected function formatCountry(?Country $country)
    {
        if (blank($country)) {
            return [];
        }

        return [
            'id' => $country->id,
            'name' => $country->name,
            'native' => $country->native
        ];
    }

    protected function formatBranch(?ManagementHierarchy $branch)
    {
        if (blank($branch)) {
            return [];
        }

        return [
            'id' => $branch->id,
            'name' => $branch->name
        ];
    }
}

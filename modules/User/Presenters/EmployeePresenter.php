<?php

declare(strict_types=1);

namespace Modules\User\Presenters;

use Modules\User\Models\User;
use Modules\JobTitle\Models\JobTitle;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Country\Models\Country;

use function PHPUnit\Framework\returnSelf;

class EmployeePresenter extends AbstractPresenter
{
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
            'branch' => $this->formatBranch($this->user->managementHierarchy->detail->branch)
        ];
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

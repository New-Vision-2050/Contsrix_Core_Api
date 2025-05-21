<?php

declare(strict_types=1);

namespace Modules\User\Presenters;

use Modules\User\Models\User;
use function PHPUnit\Framework\returnSelf;

use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Models\CompanyUserAddress;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyPresenter;

class BrokerPresenter extends AbstractPresenter
{
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    protected function present(bool $isListing = false): array
    {
        $present = [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            'status' => $this->user->status,
            "branches" => $this->formatBranches(ManagementHierarchyPresenter::collection($this->user->managementHierarchies(CompanyUserRole::BROKER->value)->get())),
        ];

        return array_merge($present, $this->formatAddress($this->user->companyUser?->nationalAddress));
    }

    protected function formatAddress(?CompanyUserAddress $address)
    {
        if (blank($address)) {
            return [];
        }

        return [
            'neighborhood_name' => $address->neighborhood_name,
            'street_name' => $address->street_name,
            'building_number' => $address->building_number,
            'additional_phone' => $address->additional_phone,
            'postal_code' => $address->postal_code,
            'country' => $address->country,
            'state' => $address->state,
            'city' => $address->city,
        ];
    }

    protected function formatBranches(?array $branches)
    {
        if (blank($branches)) {
            return [];
        }

        return array_map(function ($branch) {
            return [
                'id' => $branch['id'] ?? '',
                'name' => $branch['name'] ?? '',
                'company_id' => $branch['company_id'] ?? '',
            ];
        }, $branches);
    }
}

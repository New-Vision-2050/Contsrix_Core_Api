<?php

declare(strict_types=1);

namespace Modules\User\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\UserInfo\UserPrivilege\Models\UserPrivilege;

class UserCompaniesPresenter extends AbstractPresenter
{

    private CompanyUser $item;

    public function __construct(CompanyUser $item)
    {
        $this->item = $item;
    }


    public function present(bool $isListing = false): array
    {
        $companies = $this->item->companies;

        return [
            'user_email' => $this->item->email,
            'user_name' => $this->item->name,
            'global_id' => $this->item->global_id,
            'companies' => $companies->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'constrix_id' => $company->serial_no,
                    'domain' => $company->domains()->first()?->domain ?? null,
                    'role' => $company->pivot->role ?? null,
                    'status' => $company->pivot->status ?? null,
                    'is_active' => $company->is_active ?? null,
                    "logo" => $company->getFirstMedia("logo")?->getFullUrl(),
                ];
            })->toArray(),
            'total_companies' => $companies->count(),
        ];
    }
}

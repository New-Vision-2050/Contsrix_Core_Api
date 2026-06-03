<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;
use Modules\Shared\Privilege\Services\PrivilegeCardConfigService;

class UserPrivilegeFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->where('name', $name);
    }

    public function type(string $type)
    {
        return $this->where(function ($query) use ($type) {
            $query->whereHas('privilege', function ($privilegeQuery) use ($type) {
                $privilegeQuery->where('type', $type);
            });

            if ($type === PrivilegeCardConfigService::TYPE_HEALTH_INSURANCE) {
                $query->orWhereNotNull('medical_insurance_id');
            }
        });
    }
}

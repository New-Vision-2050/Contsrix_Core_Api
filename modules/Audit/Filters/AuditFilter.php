<?php

declare(strict_types=1);

namespace Modules\Audit\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class AuditFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->where('name', $name);
    }


    public function user($userId)
    {
        return $this->where('user_id', $userId);

    }
}

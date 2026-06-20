<?php

declare(strict_types=1);

namespace Modules\Stakeholder\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class StakeholderFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->where('name', 'LIKE', "%{$name}%");
    }

    public function status($status)
    {
        return $this->where('status', $status);
    }

    public function search($search, $filters = [])
    {
        $query = $this;

        $query->when($search, function ($q) use ($search) {
            $q->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        });

        return $query;
    }
}

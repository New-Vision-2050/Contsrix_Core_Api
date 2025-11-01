<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class EcoBrandFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->where('name', $name);
    }

    public function search($search)
    {
        return $this->where(function ($query) use ($search) {
            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('content', 'like', '%' . $search . '%');
            });   
        });
    }
}

<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class EcoCategoryFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->whereHas('translations', function ($q) use ($name) {
            $q->where('content', 'like', '%' . $name . '%');
        });
    }
    public function parent($parent_id)
    {
        return $this->where('parent_id', $parent_id);
    }
    public function description($name)
    {
        return $this->whereHas('translations', function ($q) use ($name) {
            $q->where('content', 'like', '%' . $name . '%');
        });
    }
}


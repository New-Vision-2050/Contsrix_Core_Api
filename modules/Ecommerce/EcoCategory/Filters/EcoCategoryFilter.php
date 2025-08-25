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
}


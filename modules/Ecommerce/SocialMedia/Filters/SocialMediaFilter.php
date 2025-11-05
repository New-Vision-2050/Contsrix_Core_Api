<?php

declare(strict_types=1);

namespace Modules\Ecommerce\SocialMedia\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class SocialMediaFilter extends SearchModelFilter
{
       public $relations = [];

        public function search($name)
        {
            return $this->whereHas('socialIcon',function ($q) use ($name) {
                return $q->whereLike('name', '%' . $name . '%');
            });
        }
}

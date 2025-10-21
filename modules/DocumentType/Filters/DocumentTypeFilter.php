<?php

declare(strict_types=1);

namespace Modules\DocumentType\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class DocumentTypeFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', 'like', '%' . $name . '%');
        }

        public function isActive($isActive)
        {
            return $this->where('is_active', (bool) $isActive);
        }
}

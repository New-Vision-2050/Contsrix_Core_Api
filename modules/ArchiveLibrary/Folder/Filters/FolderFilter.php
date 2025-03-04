<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class FolderFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
        public function parentId($parentId): FolderFilter
        {
            if ($parentId === 'null' || $parentId === null) {
                return $this->whereNull('parent_id');
            }

            return $this->where('parent_id', $parentId);
        }
}

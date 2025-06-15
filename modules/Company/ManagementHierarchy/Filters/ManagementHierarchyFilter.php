<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class ManagementHierarchyFilter extends SearchModelFilter
{
       public $relations = [];

    public function name($name)
    {
<<<<<<< HEAD
        return $this->where('name','LIKE', "%{$name}%");
=======
        return $this->where('name', $name);
>>>>>>> 7be6c72c (merge with stage (first version ))
    }

    public function type($type)
    {
        return $this->where('type', $type);
    }
    public function parentId($parentId)
    {
        return $this->where('parent_id', $parentId);
    }
<<<<<<< HEAD

    public function isMain($isMain)
    {
        return $this->where('is_main', $isMain);
    }
=======
>>>>>>> 7be6c72c (merge with stage (first version ))
}

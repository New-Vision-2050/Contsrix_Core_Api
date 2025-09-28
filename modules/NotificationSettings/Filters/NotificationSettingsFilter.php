<?php

declare(strict_types=1);

namespace Modules\NotificationSettings\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class NotificationSettingsFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

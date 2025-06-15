<?php

declare(strict_types=1);

namespace Modules\UserInfo\ProfessionalCertificate\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class ProfessionalCertificateFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

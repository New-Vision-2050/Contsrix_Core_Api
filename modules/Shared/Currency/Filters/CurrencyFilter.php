<?php

declare(strict_types=1);

namespace Modules\Shared\Currency\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class CurrencyFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->whereTranslatable('name', $name);
        }

        public function country($id){
            return $this->whereHas('country',function($q) use ($id){
                $q->where('id',$id);
            });
        }

        public function orderCountry($id){
            $this->join('countries','countries.currency','currencies.short_name')
                ->select('currencies.*')
                ->orderByRaw('countries.id = ? DESC', [$id]);
        }
}

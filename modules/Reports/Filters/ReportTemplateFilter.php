<?php

declare(strict_types=1);

namespace Modules\Reports\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class ReportTemplateFilter extends SearchModelFilter
{
    public $relations = [];

    public function search($search)
    {
        $search = (string) $search;

        return $this->whereHas('translations', function ($qq) use ($search) {
            $qq->whereIn('field', ['name', 'description'])
                ->where('content', 'like', '%' . $search . '%');
        });
    }

    public function is_active($value)
    {
        return $this->where('is_active', (bool) $value);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Reports\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class ReportFilter extends SearchModelFilter
{
    public $relations = [];

    public function search($search)
    {
        $search = (string) $search;

        return $this->where(function ($q) use ($search) {
            $q->whereHas('translations', function ($qq) use ($search) {
                $qq->where('field', 'name')
                    ->where('content', 'like', '%' . $search . '%');
            });
        });
    }

    public function status($status)
    {
        return $this->where('status', $status);
    }

    public function report_type($type)
    {
        return $this->whereJsonContains('report_types', $type);
    }

    public function period_type($periodType)
    {
        return $this->where('period_type', $periodType);
    }

    public function year($year)
    {
        return $this->where('year', (int) $year);
    }

    public function month($month)
    {
        return $this->where('month', (int) $month);
    }

    public function date_from($date)
    {
        return $this->whereDate('created_at', '>=', $date);
    }

    public function date_to($date)
    {
        return $this->whereDate('created_at', '<=', $date);
    }

    public function template_id($id)
    {
        return $this->where('template_id', $id);
    }
}

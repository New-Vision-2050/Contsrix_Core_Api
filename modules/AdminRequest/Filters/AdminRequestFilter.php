<?php

declare(strict_types=1);

namespace Modules\AdminRequest\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;

class AdminRequestFilter extends SearchModelFilter
{
    public $relations = [];


    public function type($type)
    {
        return $this->where('request_type', $type);
    }

    public function company($companyId)
    {
        return $this->where("company_id",$companyId);
    }

    public function branch($branchId)
    {
        return $this->when(request()->has("type"),function ($q)use($branchId){
           if(request()->type == "companyOfficialDataUpdate")
           {
               $branch = ManagementHierarchy::query()->find($branchId);
               $q->where("requestable_id",$branch->company_id);

           }elseif(request()->type == "companyLegalDataUpdate")
           {
               $q->where("requestable_id",$branchId);
           }
        });
    }


}

<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $details = \Modules\Company\ManagementHierarchy\Models\ManagementHierarchyDetail::query()->withoutParentModel()->whereNull("branch_id")->with("managementHierarchy")->get();
    foreach ($details as $detail) {

//        $branch = \Modules\Company\ManagementHierarchy\Models\ManagementHierarchy::query()->withoutTenancy()->where("company_id", $detail->managementHierarchy->company_id)
//            ->where("type", "branch")->whereNull("parent_id")->first();
        $managemant = $detail->managementHierarchy;
        while ($managemant->type !="branch")
        {

        }
        $detail->update(["branch_id" => $branch->id]);

    }});

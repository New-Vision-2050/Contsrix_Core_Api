<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        $details = \Modules\Company\ManagementHierarchy\Models\ManagementHierarchyDetail::query()->withoutParentModel()->whereNull("branch_id")->with("managementHierarchy")->get();
        foreach ($details as $detail) {

            $branch = \Modules\Company\ManagementHierarchy\Models\ManagementHierarchy::query()->withoutTenancy()->where("company_id", $detail->managementHierarchy->company_id)
                ->where("type", "branch")->whereNull("parent_id")->first();
            $detail->update(["branch_id" => $branch->id]);

        }
    }
};

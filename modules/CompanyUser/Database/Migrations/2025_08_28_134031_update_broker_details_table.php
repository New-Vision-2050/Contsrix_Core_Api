<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_08_28_134031_update_broker_details_table Migration
{
    public function up()
    {
        Schema::table('broker_details', function (Blueprint $table) {

            $table->foreignIdFor(Modules\Company\ManagementHierarchy\Models\ManagementHierarchy::class , "original_branch_id")->nullable();
        });
    }
};

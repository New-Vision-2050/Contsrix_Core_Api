<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('management_hierarchy_deputy_managers', function (Blueprint $table) {
            $table->uuid("id");
            $table->uuid("deputy_manager_id");
            $table->uuid("management_hierarchy_detail_id");
            $table->timestamps();
        });
        Schema::table('management_hierarchy_details', function (Blueprint $table) {
            $table->dropColumn("deputy_manager_id");
        });
    }
};

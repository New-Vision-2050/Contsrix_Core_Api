<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('management_hierarchy_details', function (Blueprint $table) {
            $table->uuid("id");

            $table->uuid("deputy_manager_id")->nullable();
            $table->uuid("reference_user_id")->nullable();
            $table->text("description");


        });
    }
};

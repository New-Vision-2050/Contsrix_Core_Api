<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_08_25_133816_craete_users_can_access_management_hierarchies_table Migration
{
    public function up()
    {
        Schema::create("users_can_access_management_hierarchies",function(Blueprint $table){
            $table->uuid("id")->primary();
            $table->uuid("user_id")->nullable();
            $table->unsignedBigInteger("management_hierarchy_id");
            $table->foreign('management_hierarchy_id', 'ucamh_management_hierarchy_id_fk')->references('id')->on('management_hierarchies');
            $table->foreign('user_id', 'ucamh_user_id_fk')->references('id')->on('users');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users_can_access_management_hierarchies');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_08_28_134032_update_broker_details_table Migration
{
    public function up()
    {
        Schema::table('broker_details', function (Blueprint $table) {

            $table->tinyInteger("is_created_by_owner")->default(0);
        });
    }
};

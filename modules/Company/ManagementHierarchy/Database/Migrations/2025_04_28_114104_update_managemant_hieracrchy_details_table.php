<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('management_hierarchy_details', function (Blueprint $table) {
            $table->dropColumn("id");
            $table->id()->first();
            $table->dropColumn("management_hierarchy_id");
            $table->unsignedBigInteger("management_hierarchy_id")->nullable()->index();

        });
    }
};

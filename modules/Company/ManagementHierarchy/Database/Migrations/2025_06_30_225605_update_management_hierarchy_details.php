<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('management_hierarchy_details', function (Blueprint $table) {

            $table->boolean("is_copied")->index()->default(0);

        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('management_hierarchies', function (Blueprint $table) {
            $table->renameColumn("lattitude","latitude")->nullable();
        });
    }
};

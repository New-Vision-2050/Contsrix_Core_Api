<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('admin_requests', function (Blueprint $table) {
            $table->string("serial_number")->nullable();
            $table->unique('serial_number');


        });
    }

    public function down()
    {
        Schema::table('admin_requests', function (Blueprint $table) {
            $table->dropColumn('serial_number');
        });
    }
};

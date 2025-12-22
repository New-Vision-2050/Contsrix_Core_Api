<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_10_13_140000_add_status_to_files_table Migration
{
    public function up()
    {
        Schema::table('files', function (Blueprint $table) {
            $table->integer('status')->default(1)->after('access_type');
        });
    }

    public function down()
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};

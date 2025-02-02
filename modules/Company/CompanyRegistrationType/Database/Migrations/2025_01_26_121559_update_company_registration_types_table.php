<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('company_registration_types', function (Blueprint $table) {
            $table->tinyInteger('type')->default(1)->after('name');
        });
    }

    public function down()
    {
        Schema::table('company_registration_types', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};

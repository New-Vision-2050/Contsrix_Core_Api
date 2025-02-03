<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->date('date_activate')->nullable()->after('phone');
            $table->tinyInteger('is_active')->default(0)->after('phone');
            $table->tinyInteger('complete_data')->default(0)->after('phone');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('date_activate');
            $table->dropColumn('is_active');
            $table->dropColumn('complete_data');
        });
    }
};

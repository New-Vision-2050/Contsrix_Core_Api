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
        Schema::table('company_registration_forms', function (Blueprint $table) {
            $table->string('classification_no')->nullable()->after('registration_no');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_registration_forms', function (Blueprint $table) {
            $table->dropColumn('classification_no');
        });
    }
};

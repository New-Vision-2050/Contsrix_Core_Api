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
            $table->string('company_type_id')->nullable()->change();
            $table->string('registration_type_id')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->string('phone')->nullable()->change();
            $table->string('serial_no')->nullable()->change();
            $table->string('company_type_id')->nullable()->change();
            $table->string('registration_type_id')->nullable()->change();
            $table->string('registration_no')->nullable()->change();

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
            // Reverting the changes made to the columns (making them not nullable again)
            $table->string('company_type_id')->nullable(false)->change();
            $table->string('registration_type_id')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
            $table->string('phone')->nullable(false)->change();
            $table->string('serial_no')->nullable(false)->change();
            $table->string('registration_no')->nullable(false)->change();
        });
    }

};

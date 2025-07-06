<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employment_contracts', function (Blueprint $table) {
            $table->string('nature_work_id')->nullable()->change();
            $table->string('type_working_hour_id')->nullable()->change();
            $table->string('right_terminate_id')->nullable()->change();
            $table->string('contract_duration')->nullable()->change();



            $table->string('contract_number')->nullable()->change();
            $table->date('start_date')->nullable()->change();
            $table->date('commencement_date')->nullable()->change();
            $table->string('contract_duration')->nullable()->change();

            $table->integer('notice_period')->nullable()->change();
            $table->integer('probation_period')->nullable()->change();

            $table->integer('working_hours')->nullable()->change();
            $table->integer('annual_leave')->nullable()->change();
            $table->string('country_id')->nullable()->change();
        });
    }

};

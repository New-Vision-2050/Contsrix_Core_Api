<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employment_contracts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('global_id')->index();

            $table->string('contract_number');
            $table->date('start_date');
            $table->date('commencement_date');
            $table->string('contract_duration');

            $table->integer('notice_period');
            $table->integer('probation_period');
            $table->string('nature_work');
            $table->string('type_working_hours');

            $table->integer('working_hours');
            $table->integer('annual_leave');
            $table->string('country_id');
            $table->string('right_terminate');
            
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('employment_contracts');
    }
};

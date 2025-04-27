<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employment_contracts', function (Blueprint $table) {
            $table->string('contract_duration_unit')->nullable();
            $table->string('notice_period_unit')->nullable();
            $table->string('probation_period_unit')->nullable();
        });
    }

};

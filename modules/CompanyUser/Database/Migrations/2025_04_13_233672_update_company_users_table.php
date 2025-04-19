<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('company_users', function (Blueprint $table) {
            $table->date("passport_start_date")->nullable();
            $table->date("passport_end_date")->nullable();
            $table->date("identity_start_date")->nullable();
            $table->date("identity_end_date")->nullable();
            $table->date("border_number_start_date")->nullable();
            $table->date("border_number_end_date")->nullable();
            $table->date("entry_number_start_date")->nullable();
            $table->date("entry_number_end_date")->nullable();
        });
    }
};

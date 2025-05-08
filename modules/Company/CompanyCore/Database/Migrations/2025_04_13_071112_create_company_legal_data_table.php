<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('company_legal_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid("registration_type_id")->index();
            $table->string("registration_number");
            $table->date("start_date");
            $table->date("end_date");
            $table->timestamps();
        });
    }
};

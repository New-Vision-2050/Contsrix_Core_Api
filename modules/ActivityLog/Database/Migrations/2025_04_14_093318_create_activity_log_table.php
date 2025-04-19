<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid("company_id")->nullable();
            $table->dateTime("date");
            $table->uuid("user_id");
            $table->uuid("requestable_id");
            $table->string("requestable_type");
            //action is translation in model
            $table->timestamps();
        });
    }
};

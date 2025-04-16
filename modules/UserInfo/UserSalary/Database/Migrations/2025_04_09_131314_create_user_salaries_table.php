<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_salaries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('global_id')->index();

            $table->string('basic')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->string('type')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }
};

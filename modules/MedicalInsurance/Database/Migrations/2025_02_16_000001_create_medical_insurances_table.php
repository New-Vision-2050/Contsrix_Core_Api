<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medical_insurances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('policy_number')->unique();
            $table->uuid('employee_id');
            $table->uuid('company_id');
            $table->integer('status')->default(1);
            $table->timestamps();

            $table->foreign('employee_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table->index('employee_id');
            $table->index('company_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_insurances');
    }
};

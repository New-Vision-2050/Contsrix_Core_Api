<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_insurance_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('medical_insurance_id');
            $table->uuid('company_id');
            $table->string('name');
            $table->string('type')->nullable();
            $table->decimal('coverage_limit', 15, 2);
            $table->text('description');
            $table->timestamps();

            $table->foreign('medical_insurance_id')
                ->references('id')
                ->on('medical_insurances')
                ->onDelete('cascade');

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table->index('medical_insurance_id');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_insurance_categories');
    }
};

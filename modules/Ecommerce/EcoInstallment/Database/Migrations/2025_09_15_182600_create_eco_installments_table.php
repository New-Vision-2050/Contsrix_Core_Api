<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_09_15_182600_create_eco_installments_table Migration
{
    public function up(): void
    {
        Schema::create('eco_installments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('installment_id')->index();
            $table->boolean('is_default')->default(0);
            $table->boolean('is_active')->default(1);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            // $table->foreign('installment_id')->references('id')->on('installments')->onDelete('cascade');
            
            $table->unique(['company_id', 'installment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eco_installments');
    }
};

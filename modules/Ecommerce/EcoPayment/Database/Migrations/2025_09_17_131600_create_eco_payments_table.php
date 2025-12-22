<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_09_17_131600_create_eco_payments_table Migration
{
    public function up(): void
    {
        Schema::create('eco_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('payment_id')->index();
            $table->boolean('is_default')->default(0);
            $table->boolean('is_active')->default(1);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            // $table->foreign('payment_id')->references('id')->on('payments')->onDelete('cascade');
            
            $table->unique(['company_id', 'payment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eco_payments');
    }
};

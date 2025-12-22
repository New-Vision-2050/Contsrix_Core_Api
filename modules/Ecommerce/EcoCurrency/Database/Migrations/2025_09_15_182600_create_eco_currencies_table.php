<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_09_15_182600_create_eco_currencies_table Migration
{
    public function up(): void
    {
        Schema::create('eco_currencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->string('currency_id')->index();
            $table->boolean('is_default')->default(0);
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eco_currencies');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_10_28_214000_create_store_branches_table Migration
{
    public function up(): void
    {
        Schema::create('store_branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->string('type', 50)->default('home');
            $table->string('name');
            $table->uuid('country_id')->nullable()->index();
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->decimal('latitude', 10, 8)->nullable(); // latitude
            $table->decimal('longitude', 11, 8)->nullable(); // longitude
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_branches');
    }
};

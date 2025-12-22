<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_09_15_151400_create_eco_business_activities_table Migration
{
    public function up(): void
    {
        Schema::create('eco_business_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();

            // Business information
            $table->uuid('company_field_id')->index();
            $table->string('business_name')->nullable();
            $table->string('commercial_registration_number')->nullable();
            $table->string('identity_number')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('national_identity_numbers')->nullable();
            $table->string('tax_certificate_number')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eco_business_activities');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_10_22_023300_create_flash_deals_table Migration
{
    public function up()
    {
        Schema::create('flash_deals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index()->comment('معرف الشركة');
            $table->datetime('start_date')->comment('تاريخ بداية العرض');
            $table->datetime('end_date')->comment('تاريخ انتهاء العرض');
            $table->boolean('is_active')->default(true)->comment('حالة العرض');
            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'is_active']);
            $table->index(['start_date', 'end_date']);
            $table->index('is_active');

            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('flash_deals');
    }
};

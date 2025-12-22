<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_17_141800_create_previous_works_table Migration
{
    public function up(): void
    {
        Schema::create('previous_works', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('website_service_id');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('website_service_id')
                ->references('id')
                ->on('website_services')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('previous_works');
    }
};

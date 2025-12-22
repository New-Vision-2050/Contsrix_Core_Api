<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_10_27_025300_create_features_table Migration
{
    public function up()
    {
        Schema::create('features', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('setting_page_id')->nullable()->index();
            
            // Feature information
            $table->string('title'); 
            $table->text('description');
            
            // Status and display
            $table->boolean('is_active')->default(true);

            
            // Timestamps
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('setting_page_id')->references('id')->on('setting_pages')->onDelete('set null');
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('features');
    }
};

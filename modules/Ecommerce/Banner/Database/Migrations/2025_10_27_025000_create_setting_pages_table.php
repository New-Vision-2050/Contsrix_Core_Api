<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('setting_pages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            
            // Page identification
            $table->string('type')->index(); // home, about, contact, services, etc.
            
            // Header content
            $table->string('title_header')->nullable();
            $table->text('description_header')->nullable();
            
            // Footer content
            $table->string('title_footer')->nullable();
            $table->text('description_footer')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Timestamps
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            
            $table->index('type');
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('setting_pages');
    }
};

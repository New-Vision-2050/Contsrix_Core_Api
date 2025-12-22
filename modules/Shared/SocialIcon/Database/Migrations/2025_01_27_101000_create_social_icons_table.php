<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_01_27_101000_create_social_icons_table Migration
{
    public function up()
    {
        Schema::create('social_icons', function (Blueprint $table) {
            $table->uuid('id')->primary();
        
            $table->string('name')->nullable(); // Social media platform name (Facebook, Twitter, etc.)
            $table->string('web_icon')->nullable(); // Icon path/URL for web
            $table->string('mobile_icon')->nullable(); // Icon path/URL for mobile
            $table->timestamps();
            
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('social_icons');
    }
};

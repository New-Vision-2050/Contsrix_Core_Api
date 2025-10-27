<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
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

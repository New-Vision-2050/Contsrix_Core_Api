<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('social_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('social_icons_id')->index(); // Reference to social_icons table
            $table->string('url'); // Social media URL/link
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('social_media');
    }
};

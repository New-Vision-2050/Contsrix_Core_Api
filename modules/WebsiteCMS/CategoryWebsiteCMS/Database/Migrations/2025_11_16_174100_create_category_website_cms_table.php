<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Enum\CategoryWebsiteCMSType;

return new class 2025_11_16_174100_create_category_website_cms_table Migration
{
    public function up()
    {
        Schema::create('category_website_cms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('category_type', CategoryWebsiteCMSType::values());
            $table->uuid('company_id')->nullable();
            $table->timestamps();

        });
    }

    public function down()
    {
        Schema::dropIfExists('category_website_cms');
    }
};

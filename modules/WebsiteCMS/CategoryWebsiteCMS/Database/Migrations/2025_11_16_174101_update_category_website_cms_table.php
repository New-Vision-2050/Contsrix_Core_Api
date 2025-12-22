<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Enum\CategoryWebsiteCMSType;

return new class 2025_11_16_174101_update_category_website_cms_table Migration
{
    public function up()
    {
        Schema::table('category_website_cms', function (Blueprint $table) {
            $table->enum('category_type', CategoryWebsiteCMSType::values())->change();

        });
    }

    public function down()
    {
        Schema::dropIfExists('category_website_cms');
    }
};

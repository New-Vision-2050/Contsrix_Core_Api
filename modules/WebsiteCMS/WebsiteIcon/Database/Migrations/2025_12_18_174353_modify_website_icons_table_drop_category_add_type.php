<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('website_icons', function (Blueprint $table) {
            // Drop foreign key first, then drop the column
            $table->dropForeign(['category_website_cms_id']);
            $table->dropColumn('category_website_cms_id');

            // Add new string column for category type
            $table->string('website_icon_category_type')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('website_icons', function (Blueprint $table) {
            // Remove the new column
            $table->dropColumn('website_icon_category_type');

            // Re-add the original column with foreign key
            $table->uuid('category_website_cms_id')->after('id');
            $table->foreign('category_website_cms_id')
                ->references('id')
                ->on('category_website_cms')
                ->onDelete('cascade');
        });
    }
};

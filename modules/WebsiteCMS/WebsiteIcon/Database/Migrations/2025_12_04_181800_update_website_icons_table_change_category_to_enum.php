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
//        Schema::table('website_icons', function (Blueprint $table) {
//            // Add new enum column first
//            $table->string('website_icon_category_type')->nullable('false')->after('id');
//        });

        // Optionally migrate existing data here if needed
        // DB::table('website_icons')->update(['website_icon_category_type' => 'certificates']);

        Schema::table('website_icons', function (Blueprint $table) {
            // Drop foreign key constraint
            $table->dropForeign(['category_website_cms_id']);

            // Drop the old column
            $table->dropColumn('category_website_cms_id');
        });

//        Schema::table('website_icons', function (Blueprint $table) {
//            // Make the new column not nullable after migration
//            $table->string('website_icon_category_type')->nullable(false)->change();
//        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('website_icons', function (Blueprint $table) {
            // Restore the old column
            $table->uuid('category_website_cms_id')->nullable()->after('id');

            // Restore foreign key
            $table->foreign('category_website_cms_id')
                ->references('id')
                ->on('category_website_cms')
                ->onDelete('cascade');
        });

        Schema::table('website_icons', function (Blueprint $table) {
            // Drop the enum column
            $table->dropColumn('website_icon_category_type');
        });
    }
};

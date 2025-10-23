<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('eco_brands', function (Blueprint $table) {
            $table->tinyInteger('is_active')->default(1)->after('company_id')->comment('حالة العلامة التجارية: 1 = نشط، 0 = غير مفعل');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eco_brands', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};

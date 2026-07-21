<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_order_permit', function (Blueprint $table) {
            $table->longText('import_log')->nullable()->comment('استيراد سجل التحذيرات');
        });
    }

    public function down(): void
    {
        Schema::table('project_order_permit', function (Blueprint $table) {
            $table->dropColumn('import_log');
        });
    }
};

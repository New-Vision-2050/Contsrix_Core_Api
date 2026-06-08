<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('serial_number', 30)->nullable()->after('id');
            $table->index(['company_id', 'serial_number']);
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'serial_number']);
            $table->dropColumn('serial_number');
        });
    }
};

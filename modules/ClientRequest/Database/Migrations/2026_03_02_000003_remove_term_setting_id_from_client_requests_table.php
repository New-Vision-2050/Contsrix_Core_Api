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
        Schema::table('client_requests', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['term_setting_id']);
            // Then drop the column
            $table->dropColumn('term_setting_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('term_setting_id')->nullable();
            $table->foreign('term_setting_id')
                  ->references('id')
                  ->on('term_settings')
                  ->onDelete('set null');
        });
    }
};

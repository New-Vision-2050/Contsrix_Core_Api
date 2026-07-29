<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_order_permit_note_logs', function (Blueprint $table) {
            // Drop the existing foreign key and column
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            // Recreate as uuid to match users table
            $table->uuid('user_id')->nullable()->after('project_order_permit_id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('project_order_permit_note_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            $table->unsignedBigInteger('user_id')->nullable()->after('project_order_permit_id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }
};

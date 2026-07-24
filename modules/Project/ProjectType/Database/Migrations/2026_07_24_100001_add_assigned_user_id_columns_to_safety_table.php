<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('safety_records', 'employee_id')) {
            Schema::table('safety_records', function (Blueprint $table) {
                $table->uuid('assigned_user_id')->nullable();
                $table->foreign('assigned_user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

    }

    public function down(): void
    {
        Schema::table('safety_records', function (Blueprint $table) {
            $table->dropForeign(['assigned_user_id']);
            $table->dropColumn([
                'assigned_user_id',

            ]);
        });
    }
};

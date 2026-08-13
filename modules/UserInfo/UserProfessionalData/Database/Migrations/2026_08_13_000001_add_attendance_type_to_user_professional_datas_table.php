<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_professional_datas')) {
            return;
        }

        Schema::table('user_professional_datas', function (Blueprint $table) {
            if (! Schema::hasColumn('user_professional_datas', 'attendance_type')) {
                $table->string('attendance_type', 32)->default('regular')->after('attendance_constraint_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_professional_datas')
            || ! Schema::hasColumn('user_professional_datas', 'attendance_type')) {
            return;
        }

        Schema::table('user_professional_datas', function (Blueprint $table) {
            $table->dropColumn('attendance_type');
        });
    }
};

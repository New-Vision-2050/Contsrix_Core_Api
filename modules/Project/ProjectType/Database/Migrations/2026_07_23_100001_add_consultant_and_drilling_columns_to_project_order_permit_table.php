<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_order_permit', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete()->after('consultant_price');
            $table->decimal('target_drilling', 10, 2)->nullable()->after('employee_id');
            $table->decimal('achieved_drilling', 10, 2)->nullable()->after('target_drilling');
            $table->decimal('target_extention', 10, 2)->nullable()->after('achieved_drilling');
            $table->decimal('achieved_extention', 10, 2)->nullable()->after('target_extention');
            $table->text('description_details')->nullable()->after('achieved_extention');
            $table->text('consultant_statement')->nullable()->after('description_details');
            $table->date('last_date_consultant_statement')->nullable()->after('consultant_statement');
            $table->string('consultnat_statement_status')->nullable()->after('last_date_consultant_statement');
        });
    }

    public function down(): void
    {
        Schema::table('project_order_permit', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn([
                'employee_id',
                'target_drilling',
                'achieved_drilling',
                'target_extention',
                'achieved_extention',
                'description_details',
                'consultant_statement',
                'last_date_consultant_statement',
                'consultnat_statement_status',
            ]);
        });
    }
};

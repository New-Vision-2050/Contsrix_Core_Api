<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_task_extension_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('current_procedure_step_id')->nullable()
                ->after('company_id');

            $table->foreign(
                'current_procedure_step_id',
                'et_ext_req_step_fk'
            )
            ->references('id')
            ->on('procedure_setting_steps')
            ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_task_extension_requests', function (Blueprint $table) {

            $table->dropForeign('et_ext_req_step_fk');

            $table->dropColumn('current_procedure_step_id');
        });
    }
};

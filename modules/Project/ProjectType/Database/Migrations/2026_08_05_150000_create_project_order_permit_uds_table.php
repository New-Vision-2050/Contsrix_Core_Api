<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_order_permit_uds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('company_id');

            $table->string('name')->nullable()->comment('رقم أمر العمل');
            $table->string('type_code')->nullable()->comment('رمز نوع أمر العمل');
            $table->string('executing_entity')->nullable()->comment('جهة التنفيذ');
            $table->string('office')->nullable()->comment('المكتب');
            $table->string('contractor_basket')->nullable()->comment('سلة المقاول');
            $table->string('consultant_current_basket')->nullable()->comment('سلة الاستشاري');
            $table->date('assigned_date')->nullable()->comment('تاريخ الإسناد');
            $table->date('consultant_assignment_date')->nullable()->comment('تاريخ الإسناد للاستشاري');
            $table->string('contractor_last_procedure_code')->nullable();
            $table->date('contractor_last_procedure_date')->nullable();
            $table->date('contractor_column_155_entry_date')->nullable();
            $table->string('consultant_last_procedure_code')->nullable();
            $table->date('consultant_last_procedure_date')->nullable();
            $table->date('consultant_column_155_entry_date')->nullable();
            $table->string('material_balance_elec_contractor')->nullable();
            $table->string('contractor_work_order_status')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('consultant_price', 12, 2)->nullable();
            $table->string('penalty_amount')->nullable();
            $table->date('finance_approval_date')->nullable();
            $table->string('certificate_source_number')->nullable();
            $table->string('modifier_employee_number')->nullable();
            $table->string('contractor_assigned_employee_number')->nullable();
            $table->string('work_order_status')->nullable();
            $table->string('work_order_situation')->nullable();
            $table->string('penalty_percentage')->nullable();
            $table->string('delay_duration')->nullable();
            $table->string('disbursement_status')->nullable();
            $table->string('total_cost')->nullable();
            $table->string('indirect_cost')->nullable();
            $table->string('labor_cost')->nullable();
            $table->string('consumed_material_cost')->nullable();
            $table->string('office_code')->nullable();
            $table->string('current_entity')->nullable();
            $table->string('cost_center_name')->nullable();
            $table->string('cost_center')->nullable();
            $table->string('extract_number')->nullable();
            $table->string('completion_certificate_amount')->nullable();
            $table->date('contractor_approval_cert_date')->nullable();
            $table->date('certificate_approval_date')->nullable();
            $table->date('certificate_date')->nullable();
            $table->date('procedure_203_date')->nullable();
            $table->string('last_procedure_name')->nullable();
            $table->string('work_order_type')->nullable();
            $table->string('contract_number')->nullable();
            $table->string('contractor_name')->nullable();
            $table->string('subscriber_type')->nullable()->comment('نوع المشترك — UDS only');

            $table->timestamps();

            $table->index(['project_id', 'name']);
            $table->index(['project_id', 'company_id']);

            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->cascadeOnDelete();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_order_permit_uds');
    }
};

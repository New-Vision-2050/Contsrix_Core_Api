<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uds_project_order_permit', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('company_id');
            $table->string('name')->comment('رقم أمر العمل (AI)');
            $table->string('type_code')->nullable()->comment('رمز نوع الأمر (AJ)');

            // حقول أساسية
            $table->string('executing_entity')->nullable(); // AB
            $table->string('office')->nullable(); // AL
            $table->string('contractor_basket')->nullable(); // Q
            $table->string('consultant_current_basket')->nullable(); // Q
            $table->date('assigned_date')->nullable(); // Z
            $table->date('consultant_assignment_date')->nullable(); // Z
            $table->string('contractor_last_procedure_code')->nullable(); // AE
            $table->date('contractor_last_procedure_date')->nullable(); // AC
            $table->date('contractor_column_155_entry_date')->nullable(); // Y
            $table->string('consultant_last_procedure_code')->nullable(); // AE
            $table->date('consultant_last_procedure_date')->nullable(); // AC
            $table->date('consultant_column_155_entry_date')->nullable(); // Y
            $table->string('material_balance_elec_contractor')->nullable(); // N
            $table->string('contractor_work_order_status')->nullable(); // G
            $table->decimal('price', 12, 2)->nullable(); // M (خاص بالاستشاري أو المقاول حسب الحالة)
            $table->decimal('consultant_price', 12, 2)->nullable(); // M

            // حقول إضافية من الملف (المؤشرات حسب النموذج)
            $table->decimal('penalty_amount', 12, 2)->nullable(); // 0
            $table->date('finance_approval_date')->nullable(); // 1
            $table->string('certificate_source_number')->nullable(); // 2
            $table->string('modifier_employee_number')->nullable(); // 3
            $table->string('contractor_assigned_employee_number')->nullable(); // 4
            $table->string('work_order_status')->nullable(); // 5 (حالة أمر العمل)
            $table->string('work_order_situation')->nullable(); // 6 (موقف أمر العمل العام)
            $table->decimal('penalty_percentage', 5, 2)->nullable(); // 7
            $table->string('delay_duration')->nullable(); // 8
            $table->string('disbursement_status')->nullable(); // 9
            $table->decimal('total_cost', 12, 2)->nullable(); // 10
            $table->decimal('indirect_cost', 12, 2)->nullable(); // 11
            $table->decimal('labor_cost', 12, 2)->nullable(); // 12 (لاحظ: يتعارض مع price، لذلك سنحتفظ بـ price من عمود آخر 31 ربما)
            $table->decimal('unconsumed_material_cost', 12, 2)->nullable(); // 13
            $table->decimal('consumed_material_cost', 12, 2)->nullable(); // 14
            $table->string('office_code')->nullable(); // 15
            $table->string('current_entity')->nullable(); // 16
            $table->string('cost_center_name')->nullable(); // 17
            $table->string('cost_center')->nullable(); // 18
            $table->string('extract_number')->nullable(); // 19
            $table->decimal('completion_certificate_amount', 12, 2)->nullable(); // 20
            $table->date('contractor_approval_cert_date')->nullable(); // 21
            $table->date('certificate_approval_date')->nullable(); // 22
            $table->date('certificate_date')->nullable(); // 23
            $table->date('receipt_from_contractor_date')->nullable(); // 24
            $table->date('delivery_to_contractor_date')->nullable(); // 25
            $table->date('procedure_203_date')->nullable(); // 26
            $table->string('last_procedure_name')->nullable(); // 29 (AD)
            $table->string('work_order_type')->nullable(); // 33 (AH)
            $table->string('contract_number')->nullable(); // 32
            $table->string('subscriber_type')->nullable(); // 34? سيتم تحديده لاحقًا
            $table->string('contractor_name')->nullable(); // 36 (AK)

            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->index(['project_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uds_project_order_permit');
    }
};

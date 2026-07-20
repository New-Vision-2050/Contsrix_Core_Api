<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_order_permit', function (Blueprint $table) {
            $table->timestamp('last_row_update_at')->nullable()->comment('تاريخ آخر تحديث للصف');

            $table->string('executing_entity')->nullable()->comment('جهة التنفيذ');
            $table->string('office')->nullable()->comment('المكتب');
            $table->string('consultant_current_basket')->nullable()->comment('سلة الجهة الحالية للاستشاري');
            $table->date('consultant_assignment_date')->nullable()->comment('تاريخ الاسناد للاستشاري');
            $table->string('consultant_last_procedure_code')->nullable()->comment('رمز اخر اجراء للاستشاري');
            $table->date('consultant_last_procedure_date')->nullable()->comment('تاريخ اخر اجراء للاستشاري');
            $table->date('consultant_column_155_entry_date')->nullable()->comment('تاريخ ادخال عامود 155 للاستشاري');
            $table->string('contractor_last_procedure_code')->nullable()->comment('رمز اخر اجراء للمقاول');
            $table->date('contractor_last_procedure_date')->nullable()->comment('تاريخ اخر اجراء للمقاول');
            $table->date('contractor_column_155_entry_date')->nullable()->comment('تاريخ ادخال عامود 155 للمقاول');
            $table->string('material_balance_elec_contractor')->nullable()->comment('توازن المواد بين الكهرباء والمقاول');
            $table->string('contractor_work_order_status')->nullable()->comment('موقف امر العمل للمقاول');
            $table->string('contractor_basket')->nullable()->comment('سلة جهة المقاول');

            $table->decimal('consultant_price', 12, 2)->nullable()->comment('سعر الاستشاري');
        });
    }

    public function down(): void
    {
        Schema::table('project_order_permit', function (Blueprint $table) {
            $table->dropColumn([
                'last_row_update_at',
                'executing_entity',
                'office',
                'consultant_current_basket',
                'consultant_assignment_date',
                'consultant_last_procedure_code',
                'consultant_last_procedure_date',
                'consultant_column_155_entry_date',
                'contractor_last_procedure_code',
                'contractor_last_procedure_date',
                'contractor_column_155_entry_date',
                'material_balance_elec_contractor',
                'contractor_work_order_status',
                'contractor_basket',
                'consultant_price',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_10_22_013300_create_coupons_table Migration
{
    public function up()
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index()->comment('معرف الشركة');
            $table->enum('coupon_type', ['discount_on_purchase', 'free_delivery','first_order'])->comment('نوع القسيمة - خصم على الشراء أو توصيل مجاني للطلب الأول');
            $table->string('title', 100)->comment('عنوان القسيمة');
            $table->string('code', 15)->unique()->comment('رمز القسيمة');
            $table->uuid('customer_id')->nullable()->comment('العميل');
            $table->integer('max_usage_per_user')->nullable()->comment('الحد الأقصى لنفس المستخدم');
            $table->enum('discount_type', ['percentage', 'fixed'])->comment('نوع الخصم');
            $table->decimal('discount_amount', 18, 2)->comment('مبلغ الخصم');
            $table->decimal('min_purchase', 18, 2)->default(0)->comment('الحد الأدنى للشراء');
            $table->decimal('max_discount', 18, 2)->default(0)->comment('الحد الأقصى للخصم');
            $table->date('start_date')->comment('تاريخ البدء');
            $table->date('expire_date')->comment('تاريخ الانتهاء');
            $table->boolean('is_active')->default(true)->comment('حالة القسيمة');
            $table->timestamps();

            // Indexes
            $table->index('code');
            $table->index('is_active');
            $table->index(['start_date', 'expire_date']);
            $table->index('coupon_type');
            $table->index('customer_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('coupons');
    }
};

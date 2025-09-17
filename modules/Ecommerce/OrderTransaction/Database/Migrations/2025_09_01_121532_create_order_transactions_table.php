<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('order_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index(); // Assuming 'companies' table exists and has uuid 'id'
            $table->uuid('order_id');
            $table->decimal('order_amount', 50, 2)->default(0.00);
            $table->decimal('admin_commission', 50, 2)->default(0.00);
            $table->string('received_by', 191);
            $table->string('status', 191)->nullable();
            $table->decimal('delivery_charge', 50, 2)->default(0.00);
            $table->decimal('tax', 50, 2)->default(0.00);
            $table->uuid('client_id')->nullable();
            $table->string('delivered_by', 191)->default('admin');
            $table->string('payment_method', 191)->nullable();
            $table->string('transaction_id', 191)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Drop tables in reverse order to avoid foreign key constraint issues
        Schema::dropIfExists('order_transactions');
    }
};

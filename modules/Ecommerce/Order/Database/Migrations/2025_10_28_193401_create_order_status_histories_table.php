<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_10_28_193401_create_order_status_histories_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('order_id');
            $table->string('user_type', 191);
            $table->string('status', 191);
            $table->string('cause', 191)->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['order_id']);
            $table->index(['status']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_insurance_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('medical_insurance_id');
            $table->uuid('company_id');
            $table->decimal('amount', 15, 2);
            $table->string('subscription_no')->unique();
            $table->integer('status')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('medical_insurance_id')->references('id')->on('medical_insurances')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            $table->index(['user_id', 'medical_insurance_id', 'company_id', 'status'], 'mi_subscriptions_composite_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_insurance_subscriptions');
    }
};

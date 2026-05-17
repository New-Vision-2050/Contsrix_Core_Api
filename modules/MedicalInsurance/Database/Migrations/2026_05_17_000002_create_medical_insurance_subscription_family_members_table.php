<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_insurance_subscription_family_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('medical_insurance_subscription_id');
            $table->string('name');
            $table->string('national_id');
            $table->string('relation');
            $table->decimal('amount', 15, 2);
            $table->string('subscription_no')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('medical_insurance_subscription_id', 'mi_sub_fam_members_subscription_fk')
                ->references('id')->on('medical_insurance_subscriptions')->onDelete('cascade');

            $table->index('medical_insurance_subscription_id', 'mi_sub_fam_members_subscription_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_insurance_subscription_family_members');
    }
};

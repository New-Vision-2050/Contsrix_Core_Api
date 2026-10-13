<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Employee salary-advance (سلفة) requests. Approval goes through the
     * procedure workflow (type: advance_request, form: createAdvanceRequest);
     * final status is set by the model's workflow hooks.
     */
    public function up(): void
    {
        Schema::create('advance_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('company_id');
            $table->uuid('advance_policy_id');
            $table->decimal('amount', 12, 2);
            $table->unsignedSmallInteger('installments');
            $table->text('reason');
            $table->string('status')->default('pending'); // pending | approved | rejected | cancelled
            $table->uuid('requested_by')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->uuid('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('attachments')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id']);
            $table->index(['user_id']);
            $table->index(['advance_policy_id']);
            $table->index(['status']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('advance_policy_id')->references('id')->on('advance_policies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advance_requests');
    }
};

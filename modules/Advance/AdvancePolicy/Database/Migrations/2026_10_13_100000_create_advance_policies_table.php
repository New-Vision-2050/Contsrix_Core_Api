<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Salary advance (سلفة) policies. Unlike leave there are NO types — a policy
     * stands alone and is assigned directly to users.
     */
    public function up(): void
    {
        Schema::create('advance_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name');
            $table->decimal('max_amount', 12, 2);
            $table->unsignedSmallInteger('max_installments');
            $table->unsignedSmallInteger('max_active_advances')->default(1);
            $table->boolean('requires_attachment')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'name'], 'advance_policies_company_name_unique');
            $table->index(['company_id']);

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advance_policies');
    }
};

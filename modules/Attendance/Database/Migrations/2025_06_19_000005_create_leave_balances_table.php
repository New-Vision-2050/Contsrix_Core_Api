<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLeaveBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            
            // Employee reference
            $table->string('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Leave type reference
            $table->unsignedBigInteger('leave_type_id');
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('cascade');
            
            // Balance details
            $table->integer('year');
            $table->decimal('entitled_days', 8, 2);
            $table->decimal('used_days', 8, 2)->default(0);
            $table->decimal('pending_days', 8, 2)->default(0); // Days requested but not yet approved
            $table->decimal('carryover_days', 8, 2)->default(0); // Days carried over from previous year
            
            // Balance state
            $table->dateTime('last_accrual_date')->nullable();
            $table->dateTime('expires_at')->nullable(); // When carryover days expire
            $table->text('notes')->nullable();
            
            // Audit fields
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            
            $table->timestamps();
            
            // Ensure unique balances per employee, leave type, and year
            $table->unique(['tenant_id', 'user_id', 'leave_type_id', 'year']);
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index('user_id');
            $table->index('year');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leave_balances');
    }
}

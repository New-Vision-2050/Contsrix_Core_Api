<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLeaveTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            
            // Leave type details
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            
            // Leave type settings
            $table->integer('default_days_per_year')->default(0);
            $table->boolean('requires_approval')->default(true);
            $table->boolean('is_paid')->default(true);
            $table->boolean('allow_half_day')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('min_days_notice_required')->default(0);
            $table->boolean('allow_carryover')->default(false);
            $table->integer('max_carryover_days')->default(0);
            $table->boolean('is_sick_leave')->default(false);
            $table->boolean('requires_attachment')->default(false);
            
            // Track creation and updates
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            
            // Composite unique key for tenant-specific leave types
            $table->unique(['tenant_id', 'name']);
            $table->unique(['tenant_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leave_types');
    }
}

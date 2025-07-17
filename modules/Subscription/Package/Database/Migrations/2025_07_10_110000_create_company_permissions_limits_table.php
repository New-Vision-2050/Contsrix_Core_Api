<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_permissions_limits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->uuid('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            
            $table->uuid('permission_id');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            
            $table->unsignedInteger('limit'); // Maximum allowed limit
            $table->unsignedInteger('actual_limit'); // Current remaining limit
            
            $table->timestamps();
            
            // Ensure one record per company-permission combination
            $table->unique(['company_id', 'permission_id'], 'company_permission_unique');
            
            // Indexes for performance
            $table->index('company_id');
            $table->index('permission_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_permissions_limits');
    }
};

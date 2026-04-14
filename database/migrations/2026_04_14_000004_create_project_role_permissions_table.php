<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_role_permissions', function (Blueprint $table) {
            $table->uuid('project_role_id');
            $table->uuid('project_permission_id');
            $table->timestamps();
            
            $table->foreign('project_role_id')
                ->references('id')
                ->on('project_roles')
                ->onDelete('cascade');
            
            $table->foreign('project_permission_id')
                ->references('id')
                ->on('project_permissions')
                ->onDelete('cascade');
            
            $table->primary(['project_role_id', 'project_permission_id'], 'project_role_permission_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_role_permissions');
    }
};

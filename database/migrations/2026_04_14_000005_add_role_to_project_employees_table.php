<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_employees', function (Blueprint $table) {
            $table->uuid('project_role_id')->nullable()->after('user_id');
            
            $table->foreign('project_role_id')
                ->references('id')
                ->on('project_roles')
                ->onDelete('set null');
            
            $table->index('project_role_id');
        });
    }

    public function down(): void
    {
        Schema::table('project_employees', function (Blueprint $table) {
            $table->dropForeign(['project_role_id']);
            $table->dropIndex(['project_role_id']);
            $table->dropColumn('project_role_id');
        });
    }
};

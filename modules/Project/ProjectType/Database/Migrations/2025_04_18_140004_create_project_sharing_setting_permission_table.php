<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_sharing_setting_permission', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_sharing_setting_id');
            $table->uuid('permission_id');
            $table->timestamps();

            $table->foreign('project_sharing_setting_id', 'pss_setting_fk')
                ->references('id')
                ->on('project_sharing_settings')
                ->onDelete('cascade');

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');

            $table->unique(['project_sharing_setting_id', 'permission_id'], 'pss_permission_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_sharing_setting_permission');
    }
};

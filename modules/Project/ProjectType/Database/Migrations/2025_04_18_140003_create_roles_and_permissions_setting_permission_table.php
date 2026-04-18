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
        Schema::create('roles_and_permissions_setting_permission', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('roles_and_permissions_setting_id');
            $table->uuid('permission_id');
            $table->timestamps();

            $table->foreign('roles_and_permissions_setting_id', 'raps_setting_fk')
                ->references('id')
                ->on('roles_and_permissions_settings')
                ->onDelete('cascade');

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');

            $table->unique(['roles_and_permissions_setting_id', 'permission_id'], 'raps_permission_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles_and_permissions_setting_permission');
    }
};

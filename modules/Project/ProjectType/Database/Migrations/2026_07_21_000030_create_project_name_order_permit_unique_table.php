<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_order_permit', function (Blueprint $table) {
            $table->unique(['name', 'order_permit_id'], 'project_order_permit_name_order_permit_unique');
        });
    }

    public function down(): void
    {
        Schema::table('project_order_permit', function (Blueprint $table) {
            $table->dropUnique('project_order_permit_name_order_permit_unique');
        });
    }
};

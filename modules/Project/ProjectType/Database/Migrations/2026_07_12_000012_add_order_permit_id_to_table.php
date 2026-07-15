<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_permit_department', function (Blueprint $table) {
            if (!Schema::hasColumn('order_permit_department', 'order_permit_id')) {
                $table->unsignedBigInteger('order_permit_id')->nullable();

                $table->foreign('order_permit_id')
                    ->references('id')
                    ->on('order_permit')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_permit_department', function (Blueprint $table) {
            if (Schema::hasColumn('order_permit_department', 'order_permit_id')) {
                $table->dropForeign(['order_permit_id']);
                $table->dropColumn('order_permit_id');
            }
        });
    }
};

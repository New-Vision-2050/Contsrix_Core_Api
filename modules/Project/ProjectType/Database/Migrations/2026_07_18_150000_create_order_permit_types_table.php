<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_permit_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::table('order_permit', function (Blueprint $table) {
            if (!Schema::hasColumn('order_permit', 'order_permit_type_id')) {
                $table->unsignedBigInteger('order_permit_type_id')->nullable()->after('order_permit_department_id');

                $table->foreign('order_permit_type_id')
                    ->references('id')
                    ->on('order_permit_types')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_permit', function (Blueprint $table) {
            if (Schema::hasColumn('order_permit', 'order_permit_type_id')) {
                $table->dropForeign(['order_permit_type_id']);
                $table->dropColumn('order_permit_type_id');
            }
        });

        Schema::dropIfExists('order_permit_types');
    }
};

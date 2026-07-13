<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::dropIfExists('project_order_permit');

        Schema::create('project_order_permit', function (Blueprint $table) {
            $table->id();
            $table->uuid('project_id');
            $table->unsignedBigInteger('order_permit_id')->nullable();
            $table->unsignedBigInteger('order_permit_department_id')->nullable();
            $table->uuid('contractor_id')->nullable();
            $table->string('name')->nullable();
            $table->string('type')->nullable();
            $table->date('assigned_date')->nullable();
            $table->unsignedMediumInteger('state_id')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('long', 11, 8)->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->timestamps();

            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->onDelete('cascade');

            $table->foreign('order_permit_id')
                ->references('id')
                ->on('order_permit')
                ->onDelete('set null');

            $table->foreign('order_permit_department_id')
                ->references('id')
                ->on('order_permit_department')
                ->onDelete('set null');

            $table->foreign('contractor_id')
                ->references('id')
                ->on('project_contractors')
                ->onDelete('set null');

            $table->foreign('state_id')
                ->references('id')
                ->on('states')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_order_permit');
    }
};

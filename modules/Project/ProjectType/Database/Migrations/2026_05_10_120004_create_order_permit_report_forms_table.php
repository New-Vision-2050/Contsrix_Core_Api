<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_permit_report_forms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_type_id');
            $table->unsignedBigInteger('order_permit_procedure_id');
            $table->string('name', 255)->nullable();
            $table->string('question', 255)->nullable();
            $table->string('value', 255)->nullable();
            $table->string('number_of_attachments', 255)->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->foreign('project_type_id')
                ->references('id')
                ->on('project_types')
                ->onDelete('cascade');

            $table->foreign('order_permit_procedure_id')
                ->references('id')
                ->on('order_permit_procedure')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_permit_report_forms');
    }
};

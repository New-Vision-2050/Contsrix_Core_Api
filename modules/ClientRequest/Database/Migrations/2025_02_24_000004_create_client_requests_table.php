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
        Schema::create('client_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->foreignId('client_request_type_id')->constrained('client_request_types')->onDelete('cascade');
            $table->foreignId('client_request_receiver_from_id')->constrained('client_request_receiver_from')->onDelete('cascade');
            $table->string('client_type');
            $table->uuid('client_id');
            $table->text('content')->nullable();
            $table->unsignedBigInteger('term_setting_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('management_id')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('term_setting_id')->references('id')->on('term_settings')->onDelete('set null');
            $table->foreign('branch_id')->references('id')->on('management_hierarchies')->onDelete('set null');
            $table->foreign('management_id')->references('id')->on('management_hierarchies')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_requests');
    }
};

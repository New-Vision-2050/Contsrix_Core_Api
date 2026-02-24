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
        Schema::create('client_request_service_pivot', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_request_id');
            $table->foreignId('client_request_service_id')->constrained('client_request_services')->onDelete('cascade');
            $table->timestamps();

            $table->foreign('client_request_id')->references('id')->on('client_requests')->onDelete('cascade');
            $table->unique(['client_request_id', 'client_request_service_id'], 'client_request_service_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_request_service_pivot');
    }
};

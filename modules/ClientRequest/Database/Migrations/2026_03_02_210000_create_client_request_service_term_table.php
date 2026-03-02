<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('client_request_service_term', function (Blueprint $table) {
            $table->uuid('client_request_id');
            $table->unsignedBigInteger('client_request_service_id');
            $table->json('term_ids')->nullable(); // Store array of term_setting_ids as JSON

            $table->foreign('client_request_id')
                  ->references('id')
                  ->on('client_requests')
                  ->onDelete('cascade');

            $table->foreign('client_request_service_id')
                  ->references('id')
                  ->on('client_request_services')
                  ->onDelete('cascade');

            $table->primary(['client_request_id', 'client_request_service_id']);

            $table->index('client_request_id', 'crs_service_term_request_idx');
            $table->index('client_request_service_id', 'crs_service_term_service_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('client_request_service_term');
    }
};

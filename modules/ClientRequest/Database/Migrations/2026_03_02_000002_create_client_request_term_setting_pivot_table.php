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
        Schema::create('client_request_term_setting', function (Blueprint $table) {
            $table->uuid('client_request_id');
            $table->unsignedBigInteger('term_setting_id');
            
            $table->foreign('client_request_id')
                  ->references('id')
                  ->on('client_requests')
                  ->onDelete('cascade');
                  
            $table->foreign('term_setting_id')
                  ->references('id')
                  ->on('term_settings')
                  ->onDelete('cascade');
                  
            $table->primary(['client_request_id', 'term_setting_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_request_term_setting');
    }
};

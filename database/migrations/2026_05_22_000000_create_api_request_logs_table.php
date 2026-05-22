<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('company_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('method', 10);
            $table->string('path', 500);
            $table->string('route_name', 255)->nullable();
            $table->string('feature', 100)->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 1023)->nullable();
            $table->json('request_headers')->nullable();
            $table->longText('request_payload')->nullable();
            $table->longText('response_body')->nullable();
            $table->timestamps();

            $table->index(['feature', 'created_at']);
            $table->index(['path', 'created_at']);
            $table->index(['company_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
    }
};

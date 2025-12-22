<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_08_28_121532_create_eco_complaints_table Migration
{
    public function up()
    {
        Schema::create('eco_complaints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->text('name');
            $table->string('status')->default('pending')->index(); // e.g., pending, in_progress, resolved, closed
            $table->foreignUuid('eco_client_id')->constrained('eco_clients')->onDelete('cascade');
            $table->timestamps();
        });
    }
};

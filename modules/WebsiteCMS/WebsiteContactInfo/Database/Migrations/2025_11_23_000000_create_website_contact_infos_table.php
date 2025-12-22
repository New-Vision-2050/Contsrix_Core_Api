<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_23_000000_create_website_contact_infos_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('website_contact_infos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table->unique('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_contact_infos');
    }
};

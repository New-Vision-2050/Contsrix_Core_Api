<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_30_180000_create_website_contact_messages_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('website_contact_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('phone', 20);
            $table->string('email');
            $table->string('address', 500)->nullable();
            $table->integer('status')->default(0);
            $table->text('message');
            $table->uuid('company_id');
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table->index('company_id');
            $table->index('status');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_contact_messages');
    }
};

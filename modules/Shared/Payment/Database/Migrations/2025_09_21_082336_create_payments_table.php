<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_09_21_082336_create_payments_table Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

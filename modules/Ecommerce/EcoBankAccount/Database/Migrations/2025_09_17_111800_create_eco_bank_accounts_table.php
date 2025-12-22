<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_09_17_111800_create_eco_bank_accounts_table Migration
{
    public function up(): void
    {
        Schema::create('eco_bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->string('bank_id');
            $table->string('account_holder_name');
            $table->string('account_number');
            $table->string('iban');
            $table->string('country_id');
            $table->boolean('is_primary')->default(0);
            $table->boolean('is_active')->default(1);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eco_bank_accounts');
    }
};

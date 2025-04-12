<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('user_id')->index();
            $table->uuid('country_id')->index();
            $table->uuid('bank_id')->index();
            $table->uuid('currency_id')->index();
            $table->string('user_name');
            $table->string('account_number');
            $table->string('iban');
            $table->string('swift_bic');
            $table->timestamps();
        });
    }
};

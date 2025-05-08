<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('company_company_fields', function (Blueprint $table) {
            $table->uuid('company_id');
            $table->uuid('company_field_id');
            $table->timestamps();
        });
    }
};

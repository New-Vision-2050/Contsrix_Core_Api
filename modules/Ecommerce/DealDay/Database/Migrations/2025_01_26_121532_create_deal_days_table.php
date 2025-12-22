<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_01_26_121532_create_deal_days_table Migration
{
    public function up()
    {
        Schema::create('deal_days', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('product_id')->index();
            $table->string('discount_type');
            $table->decimal('discount_value', 8, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};

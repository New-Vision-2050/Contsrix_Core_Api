<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('feature_deals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('discount_type');
            $table->decimal('discount_value', 8, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};

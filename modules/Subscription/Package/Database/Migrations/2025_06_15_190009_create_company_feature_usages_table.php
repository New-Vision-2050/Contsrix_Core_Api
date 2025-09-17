<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::create('company_feature_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('feature_id');
            $table->unsignedBigInteger('usage')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'feature_id'], 'usage_scope_unique');
        });
    }
};

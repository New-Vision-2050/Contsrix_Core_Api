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
            $table->string('scope_key')->nullable(); // e.g. '2025-11', '2025-11-21' or '2025'
            $table->uuid('scope_entity_id')->nullable(); // e.g. user_id or package_id ...
            $table->unsignedBigInteger('usage')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'feature_id', 'scope_key', 'scope_entity_id'], 'usage_scope_unique');
        });
    }
};

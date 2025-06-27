<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::create('package_features', function (Blueprint $table) {

            $table->uuid('id')->primary();
            $table->json('name');
            $table->string('slug');
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('limit')->nullable(); // optional usage limit
            $table->string('limit_type')->nullable();     // e.g., per_day, per_month, per_user, gb, minutes, per_package

            $table->uuid('module_id');
            $table->uuid('package_id');

            $table->foreign('package_id')->references('id')->on('packages')->onDelete('cascade');
            $table->foreign('module_id')->references('id')->on('modules')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_feature_settings');
    }
};

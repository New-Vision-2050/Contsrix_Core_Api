<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::create('package_feature_settings', function (Blueprint $table) {
            $table->uuid('package_id');
            $table->uuid('feature_id');

            $table->boolean('is_enabled')->default(true);

            $table->unsignedInteger('limit')->nullable(); // optional usage limit
            $table->string('limit_type')->nullable();     // e.g., per_day, per_month, per_user, gb, minutes, per_package

            $table->timestamps();

            $table->foreign('package_id')->references('id')->on('packages')->onDelete('cascade');
            $table->foreign('feature_id')->references('id')->on('features')->onDelete('cascade');

            $table->unique(['package_id', 'feature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_feature_settings');
    }
};

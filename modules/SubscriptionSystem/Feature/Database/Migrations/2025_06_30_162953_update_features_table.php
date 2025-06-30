<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->uuid('featureable_id')->nullable()->after('slug');
            $table->string('featureable_type')->nullable()->after('featureable_id');

            $table->index(['featureable_id', 'featureable_type']);
        });
    }

    public function down(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->dropIndex(['featureable_id', 'featureable_type']);
            $table->dropColumn(['featureable_id', 'featureable_type']);
        });
    }
};

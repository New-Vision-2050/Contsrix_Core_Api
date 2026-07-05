<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('contractual_engagements', 'code')) {
            return;
        }

        Schema::table('contractual_engagements', function (Blueprint $table) {
            $table->string('code')->unique()->nullable()->after('name_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('contractual_engagements', 'code')) {
            return;
        }

        Schema::table('contractual_engagements', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};

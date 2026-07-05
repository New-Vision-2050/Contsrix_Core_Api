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
        Schema::table('projects', function (Blueprint $table) {
            $table->uuid('contractual_engagement_id')->nullable()->after('contract_id');
            $table->index('contractual_engagement_id');

            $table->foreign('contractual_engagement_id')
                ->references('id')
                ->on('contractual_engagements')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['contractual_engagement_id']);
            $table->dropIndex(['contractual_engagement_id']);
            $table->dropColumn('contractual_engagement_id');
        });
    }
};

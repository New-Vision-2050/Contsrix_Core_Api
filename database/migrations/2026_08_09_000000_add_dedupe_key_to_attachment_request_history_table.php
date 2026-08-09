<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachment_request_history', function (Blueprint $table): void {
            $table->string('dedupe_key', 64)->nullable()->after('metadata');
            $table->unique('dedupe_key', 'attachment_request_history_dedupe_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('attachment_request_history', function (Blueprint $table): void {
            $table->dropUnique('attachment_request_history_dedupe_key_unique');
            $table->dropColumn('dedupe_key');
        });
    }
};

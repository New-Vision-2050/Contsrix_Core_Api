<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachment_request_history', function (Blueprint $table): void {
            $table->unsignedInteger('sort_order')->nullable()->after('dedupe_key');
            $table->index(['attachment_request_id', 'sort_order'], 'attachment_request_history_request_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::table('attachment_request_history', function (Blueprint $table): void {
            $table->dropIndex('attachment_request_history_request_sort_idx');
            $table->dropColumn('sort_order');
        });
    }
};

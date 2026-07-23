<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attachment_requests')
            || Schema::hasColumn('attachment_requests', 'receiver_company_id')) {
            return;
        }

        Schema::table('attachment_requests', function (Blueprint $table): void {
            $table->uuid('receiver_company_id')->nullable()->after('sender_company_id')->index();

            $table->foreign('receiver_company_id')
                ->references('id')
                ->on('companies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('attachment_requests')
            || ! Schema::hasColumn('attachment_requests', 'receiver_company_id')) {
            return;
        }

        Schema::table('attachment_requests', function (Blueprint $table): void {
            $table->dropForeign(['receiver_company_id']);
            $table->dropIndex(['receiver_company_id']);
            $table->dropColumn('receiver_company_id');
        });
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attachment_requests')
            || ! Schema::hasColumn('attachment_requests', 'receiver_company_id')) {
            return;
        }

        $foreignKeyName = $this->receiverCompanyForeignKeyName();
        if ($foreignKeyName !== false) {
            Schema::table('attachment_requests', function (Blueprint $table) use ($foreignKeyName): void {
                $table->dropForeign($foreignKeyName ?: ['receiver_company_id']);
            });
        }

        if (Schema::hasIndex('attachment_requests', ['receiver_company_id'])) {
            Schema::table('attachment_requests', function (Blueprint $table): void {
                $table->dropIndex(['receiver_company_id']);
            });
        }

        Schema::table('attachment_requests', function (Blueprint $table): void {
            $table->dropColumn('receiver_company_id');
        });
    }

    public function down(): void
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

    private function receiverCompanyForeignKeyName(): string|false
    {
        foreach (Schema::getForeignKeys('attachment_requests') as $foreignKey) {
            if (($foreignKey['columns'] ?? []) === ['receiver_company_id']) {
                return (string) ($foreignKey['name'] ?? '');
            }
        }

        return false;
    }
};

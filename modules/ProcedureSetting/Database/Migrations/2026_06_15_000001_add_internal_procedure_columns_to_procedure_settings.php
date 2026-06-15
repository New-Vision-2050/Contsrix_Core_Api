<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procedure_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('procedure_settings', 'parent_id')) {
                $table->uuid('parent_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('procedure_settings', 'form')) {
                $table->string('form', 100)->nullable()->after('type');
            }

            if (! Schema::hasColumn('procedure_settings', 'conditions')) {
                $table->json('conditions')->nullable()->after('form');
            }

            if (! Schema::hasColumn('procedure_settings', 'appears_before_id')) {
                $table->uuid('appears_before_id')->nullable()->after('conditions');
            }

            if (! Schema::hasColumn('procedure_settings', 'appears_after_id')) {
                $table->uuid('appears_after_id')->nullable()->after('appears_before_id');
            }
        });

        Schema::table('procedure_settings', function (Blueprint $table) {
            if (
                Schema::hasColumn('procedure_settings', 'parent_id')
                && ! $this->foreignExists('ps_parent_fk')
            ) {
                $table->foreign('parent_id', 'ps_parent_fk')
                    ->references('id')
                    ->on('procedure_settings')
                    ->cascadeOnDelete();
            }

            if (
                Schema::hasColumn('procedure_settings', 'appears_before_id')
                && ! $this->foreignExists('ps_appears_before_fk')
            ) {
                $table->foreign('appears_before_id', 'ps_appears_before_fk')
                    ->references('id')
                    ->on('procedure_settings')
                    ->nullOnDelete();
            }

            if (
                Schema::hasColumn('procedure_settings', 'appears_after_id')
                && ! $this->foreignExists('ps_appears_after_fk')
            ) {
                $table->foreign('appears_after_id', 'ps_appears_after_fk')
                    ->references('id')
                    ->on('procedure_settings')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('procedure_settings', function (Blueprint $table) {
            $table->dropForeignIfExists('ps_parent_fk');
            $table->dropForeignIfExists('ps_appears_before_fk');
            $table->dropForeignIfExists('ps_appears_after_fk');

            foreach (['parent_id', 'form', 'conditions', 'appears_before_id', 'appears_after_id'] as $col) {
                if (Schema::hasColumn('procedure_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function foreignExists(string $name): bool
    {
        $connection = Schema::getConnection();
        $grammar    = $connection->getDoctrineSchemaManager();

        try {
            $fks = $grammar->listTableForeignKeys('procedure_settings');
            foreach ($fks as $fk) {
                if ($fk->getName() === $name) {
                    return true;
                }
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }
};

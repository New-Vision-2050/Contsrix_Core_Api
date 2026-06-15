<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicate child (internal procedure) rows before adding the unique constraint.
        // Keep the row with the lowest sort_order (or earliest created_at as fallback).
        $duplicates = DB::table('procedure_settings')
            ->select('parent_id', 'form')
            ->whereNotNull('parent_id')
            ->whereNotNull('form')
            ->groupBy('parent_id', 'form')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            $idsToKeep = DB::table('procedure_settings')
                ->where('parent_id', $dup->parent_id)
                ->where('form', $dup->form)
                ->orderBy('sort_order', 'asc')
                ->orderBy('created_at', 'asc')
                ->pluck('id')
                ->all();

            if (count($idsToKeep) > 1) {
                $keep = array_shift($idsToKeep);
                DB::table('procedure_settings')
                    ->whereIn('id', $idsToKeep)
                    ->delete();
            }
        }

        Schema::table('procedure_settings', function (Blueprint $table) {
            if (! $this->indexExists('procedure_settings', 'ps_parent_form_unique')) {
                $table->unique(['parent_id', 'form'], 'ps_parent_form_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('procedure_settings', function (Blueprint $table) {
            try {
                $table->dropUnique('ps_parent_form_unique');
            } catch (\Throwable) {
                // Index may not exist
            }
        });
    }

    private function indexExists(string $table, string $name): bool
    {
        try {
            return in_array($name, array_map(
                fn (array $i) => $i['name'],
                Schema::getIndexes($table)
            ), true);
        } catch (\Throwable) {
            return false;
        }
    }
};

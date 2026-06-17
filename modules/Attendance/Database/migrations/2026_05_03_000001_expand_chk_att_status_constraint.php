<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            $this->dropCheckConstraintIfExists('chk_att_status');
            DB::statement("ALTER TABLE attendances ADD CONSTRAINT chk_att_status CHECK (status IN ('waiting','active','completed','pending_approval','approved','rejected','absent','holiday'))");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            $this->dropCheckConstraintIfExists('chk_att_status');
            DB::statement("ALTER TABLE attendances ADD CONSTRAINT chk_att_status CHECK (status IN ('waiting','active','completed','pending_approval','approved','rejected'))");
        }
    }

    private function dropCheckConstraintIfExists(string $name): void
    {
        $exists = DB::selectOne(
            "SELECT COUNT(*) AS cnt FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'attendances'
             AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'CHECK'",
            [$name]
        );

        if ($exists && $exists->cnt > 0) {
            DB::statement("ALTER TABLE attendances DROP CONSTRAINT {$name}");
        }
    }
};

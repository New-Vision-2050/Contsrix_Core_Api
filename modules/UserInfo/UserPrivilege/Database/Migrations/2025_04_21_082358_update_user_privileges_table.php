<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_privileges', function (Blueprint $table) {
            $table->renameColumn('type_privilege', 'type_privilege_id');
            $table->renameColumn('type_allowance', 'type_allowance_id');
            $table->renameColumn('period', 'period_id');
        });
    }

    public function down(): void
    {
        Schema::table('user_privileges', function (Blueprint $table) {
            $table->renameColumn('period_id', 'period');
            $table->renameColumn('type_allowance_id', 'type_allowance');
            $table->renameColumn('type_privilege_id', 'type_privilege');
        });
    }
};

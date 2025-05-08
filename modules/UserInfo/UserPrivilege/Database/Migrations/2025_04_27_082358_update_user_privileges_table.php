<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_privileges', function (Blueprint $table) {
            $table->renameColumn('type_allowance_id', 'type_allowance_code');
        });
    }

    public function down(): void
    {
        Schema::table('user_privileges', function (Blueprint $table) {
            $table->renameColumn('type_allowance_code', 'type_allowance_id');
        });
    }
};

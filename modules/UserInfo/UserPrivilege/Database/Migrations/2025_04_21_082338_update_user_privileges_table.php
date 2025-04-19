<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_privileges', function (Blueprint $table) {
            $table->renameColumn('rate', 'charge_amount');

        });
    }

    public function down(): void
    {
        Schema::table('user_privileges', function (Blueprint $table) {
            $table->renameColumn('charge_amount', 'rate');
        });
    }
};

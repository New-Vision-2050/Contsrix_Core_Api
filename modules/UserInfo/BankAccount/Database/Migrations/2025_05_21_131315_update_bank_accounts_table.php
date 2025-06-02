<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
                        $table->dropColumn('type');
            $table->uuid('type_id')->nullable()->after('id');

        });
    }

    public function down()
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn('type_id');
            $table->enum('type', ['salaries', 'custody', 'default'])->nullable()->default('custody')->after('id');
        });
    }
};

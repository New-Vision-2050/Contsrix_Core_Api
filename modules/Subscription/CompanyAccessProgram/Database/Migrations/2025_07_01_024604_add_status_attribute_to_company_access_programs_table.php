<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::table('company_access_programs', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name');
        });
    }

    public function down()
    {
        Schema::table('company_access_programs', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};

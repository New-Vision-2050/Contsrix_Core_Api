<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::table('registration_forms', function (Blueprint $table) {
            Schema::table('registration_forms', function (Blueprint $table) {
                $table->unsignedTinyInteger('company_user_role_map')->nullable()->after('slug');
            });
        });
    }

    public function down(): void
    {
        Schema::table('registration_forms', function (Blueprint $table) {
            $table->dropColumn('company_user_role_map');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('registration_form_id')->nullable()->after('id');

            $table->foreign('registration_form_id')
                ->references('id')
                ->on('registration_forms')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['registration_form_id']);
            $table->dropColumn('registration_form_id');
        });
    }
};

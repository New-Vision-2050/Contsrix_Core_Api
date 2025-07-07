<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('resource')->after('name');
            $table->string('action')->after('resource');
            $table->uuid('program_id')->nullable()->after('resource');
            $table->uuid('sub_entity_id')->nullable()->after('program_id');

            $table->foreign('program_id')->references('id')->on('programs')->onDelete('cascade');
            $table->foreign('sub_entity_id')->references('id')->on('sub_entities')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->dropForeign(['sub_entity_id']);
            $table->dropColumn(['resource', 'program_id', 'sub_entity_id', 'action']);
        });
    }
};

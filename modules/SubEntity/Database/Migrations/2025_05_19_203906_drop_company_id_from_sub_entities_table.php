<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('sub_entities', 'company_id')) {
            Schema::table('sub_entities', function (Blueprint $table) {
                $table->dropColumn('company_id');
            });
        }
    }


    public function down()
    {
        Schema::table('sub_entities', function (Blueprint $table) {
            $table->uuid('company_id')->index()->nullable();
        });
    }
};

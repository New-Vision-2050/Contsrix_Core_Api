<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::table('management_hierarchies', function (Blueprint $table) {
//             $table->dropColumn("parent_id");
//             $table->unsignedBigInteger("parent_id")->nullable()->after("path");
        });
    }
};

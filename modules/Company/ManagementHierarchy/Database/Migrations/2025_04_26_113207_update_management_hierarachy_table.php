<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::table('management_hierarchies', function (Blueprint $table) {
            $table->boolean("is_main")->default(0)->index();
            $table->boolean("is_active")->default(1)->index();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::table('company_users', function (Blueprint $table) {
            $table->softDeletes();
            $table->dropColumn("phone");
            $table->dropColumn("phone_code");
        });
    }
};

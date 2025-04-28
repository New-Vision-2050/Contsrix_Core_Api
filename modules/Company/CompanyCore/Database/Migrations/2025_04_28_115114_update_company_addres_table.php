<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::table('company_address', function (Blueprint $table) {
            $table->dropColumn("management_hierarchy_id");
            $table->unsignedBigInteger("management_hierarchy_id")->nullable()->index();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::table('management_hierarchies', function (Blueprint $table) {
            $table->string('manageable_type')->nullable()->after('type');
            $table->unsignedBigInteger('manageable_id')->nullable()->after('manageable_type');
            $table->index(['manageable_type', 'manageable_id']);
        });
    }

    public function down()
    {
        Schema::table('management_hierarchies', function (Blueprint $table) {
            $table->dropIndex(['manageable_type', 'manageable_id']);
            $table->dropColumn(['manageable_type', 'manageable_id']);
        });
    }
};

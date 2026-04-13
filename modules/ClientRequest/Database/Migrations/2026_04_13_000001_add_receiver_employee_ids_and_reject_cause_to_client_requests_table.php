<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('client_requests', function (Blueprint $table) {
            $table->text('reject_cause')->nullable()->after('receiver_employee_id');
        });
    }

    public function down()
    {
        Schema::table('client_requests', function (Blueprint $table) {
            $table->dropColumn(['reject_cause']);
        });
    }
};

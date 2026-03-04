<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('client_requests', function (Blueprint $table) {
            $table->string('receiver_phone')->nullable()->after('content');
            $table->string('receiver_email')->nullable()->after('receiver_phone');
            $table->string('receiver_broker_type')->nullable()->after('receiver_email');
            $table->uuid('receiver_broker_id')->nullable()->after('receiver_broker_type');
            $table->uuid('receiver_employee_id')->nullable()->after('receiver_broker_id');
        });
    }

    public function down()
    {
        Schema::table('client_requests', function (Blueprint $table) {
            $table->dropColumn([
                'receiver_phone',
                'receiver_email',
                'receiver_broker_type',
                'receiver_broker_id',
                'receiver_employee_id',
            ]);
        });
    }
};

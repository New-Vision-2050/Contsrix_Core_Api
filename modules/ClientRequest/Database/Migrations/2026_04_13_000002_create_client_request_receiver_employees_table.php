<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('client_request_receiver_employees', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_request_id');
            $table->uuid('user_id');
            $table->timestamps();

            $table->foreign('client_request_id', 'cr_recv_emp_cr_id_fk')
                ->references('id')
                ->on('client_requests')
                ->onDelete('cascade');

            $table->unique(['client_request_id', 'user_id'], 'cr_recv_emp_cr_user_unique');
            $table->index('user_id', 'cr_recv_emp_user_id_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('client_request_receiver_employees');
    }
};

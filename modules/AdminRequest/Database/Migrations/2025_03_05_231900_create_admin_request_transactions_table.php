<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('admin_request_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('admin_request_id');
            $table->uuid("requestable_id");
            $table->string("requestable_type");
            $table->string("action");
            $table->json("data");
            $table->enum("status",\Modules\AdminRequest\Enum\AdminRequestStatus::values())->default(\Modules\AdminRequest\Enum\AdminRequestStatus::PENDING->value);
            $table->timestamps();
        });
    }
};

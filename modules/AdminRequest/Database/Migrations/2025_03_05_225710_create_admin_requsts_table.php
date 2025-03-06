<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('admin_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid("user_id");
            $table->string("request_type");//like update official data for company will be specific key fot
            $table->json("data");
            $table->enum("status",\Modules\AdminRequest\Enum\AdminRequestStatus::values())->default(\Modules\AdminRequest\Enum\AdminRequestStatus::PENDING->value);
            $table->timestamps();
        });
    }
};

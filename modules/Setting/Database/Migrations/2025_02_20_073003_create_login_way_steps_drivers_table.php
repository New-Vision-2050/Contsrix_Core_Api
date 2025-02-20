<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('login_way_steps_drivers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(\Modules\Setting\Models\Driver::class , "driver_id")->index();
            $table->foreignIdFor(\Modules\Setting\Models\LoginWayStep::class , "login_way_step_id")->index();
            $table->timestamps();
        });
    }
};

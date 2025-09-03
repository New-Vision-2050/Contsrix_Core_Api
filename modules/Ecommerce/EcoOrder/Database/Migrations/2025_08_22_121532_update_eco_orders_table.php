<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eco_orders', function (Blueprint $table) {
            $table->timestamp('returned_at')->nullable()->after('delivery_type');
        });
    }

};

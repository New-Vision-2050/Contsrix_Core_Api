<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('client_requests', function (Blueprint $table) {
            $table->enum('client_price_offer_status', ['pending', 'rejected', 'accepted'])
                ->default('pending')
                ->after('status_client_request');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_requests', function (Blueprint $table) {
            $table->dropColumn('client_price_offer_status');
        });
    }
};

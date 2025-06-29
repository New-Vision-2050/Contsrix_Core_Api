<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->decimal('price', 10, 2); // Subscription price
            $table->uuid('currency_id')->nullable()->index(); // Linked currency
            $table->string('billing_cycle'); // Subscription duration: daily, monthly, yearly

            $table->integer('trial_period')->nullable(); // Trial period value
            $table->string('trial_period_type')->nullable(); // Trial period type: day, month, year

            $table->boolean('is_active')->default(true); // Whether the package is active
            $table->timestamps();
        });
        
    }
};

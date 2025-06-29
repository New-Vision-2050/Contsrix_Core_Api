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
            $table->json('name');
            $table->decimal('price', 10, 2); // e.g., 99999999.99 max
            $table->string('billing_cycle'); // e.g., 'monthly', 'yearly'

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};

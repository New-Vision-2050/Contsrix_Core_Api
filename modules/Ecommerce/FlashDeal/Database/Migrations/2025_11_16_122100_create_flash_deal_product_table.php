<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_16_122100_create_flash_deal_product_table Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('flash_deal_product')) {
            Schema::create('flash_deal_product', function (Blueprint $table) {
                $table->uuid('flash_deal_id');
                $table->uuid('product_id');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('flash_deal_product');
    }
};


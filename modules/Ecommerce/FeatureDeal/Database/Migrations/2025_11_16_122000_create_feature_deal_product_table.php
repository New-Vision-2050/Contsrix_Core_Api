<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_16_122000_create_feature_deal_product_table Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('feature_deal_product')) {
            Schema::create('feature_deal_product', function (Blueprint $table) {
                $table->uuid('feature_deal_id');
                $table->uuid('product_id');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_deal_product');
    }
};


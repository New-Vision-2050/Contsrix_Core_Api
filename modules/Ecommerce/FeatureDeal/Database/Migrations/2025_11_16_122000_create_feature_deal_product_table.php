<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_deal_product', function (Blueprint $table) {
            $table->uuid('feature_deal_id');
            $table->uuid('product_id');
            $table->timestamps();

            $table->primary(['feature_deal_id', 'product_id'], 'feature_deal_product_primary');

            $table->foreign('feature_deal_id')
                ->references('id')
                ->on('feature_deals')
                ->onDelete('cascade');

            $table->foreign('product_id')
                ->references('id')
                ->on('eco_products')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_deal_product');
    }
};


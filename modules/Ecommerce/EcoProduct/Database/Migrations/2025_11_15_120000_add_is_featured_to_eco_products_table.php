<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_15_120000_add_is_featured_to_eco_products_table Migration
{
    public function up(): void
    {
        Schema::table('eco_products', function (Blueprint $table) {
            if (!Schema::hasColumn('eco_products', 'is_featured')) {
                $table->boolean('is_featured')
                    ->default(0)
                    ->after('is_visible');
            }
        });
    }

    public function down(): void
    {
        Schema::table('eco_products', function (Blueprint $table) {
            if (Schema::hasColumn('eco_products', 'is_featured')) {
                $table->dropColumn('is_featured');
            }
        });
    }
};


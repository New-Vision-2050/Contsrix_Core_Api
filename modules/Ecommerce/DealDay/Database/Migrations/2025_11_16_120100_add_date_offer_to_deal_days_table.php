<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_16_120100_add_date_offer_to_deal_days_table Migration
{
    public function up(): void
    {
        Schema::table('deal_days', function (Blueprint $table) {
            if (!Schema::hasColumn('deal_days', 'date_offer')) {
                $table->date('date_offer')->nullable()->after('product_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deal_days', function (Blueprint $table) {
            if (Schema::hasColumn('deal_days', 'date_offer')) {
                $table->dropColumn('date_offer');
            }
        });
    }
};


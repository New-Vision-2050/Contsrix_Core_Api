<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deal_days', function (Blueprint $table) {
            if (!Schema::hasColumn('deal_days', 'date_offer')) {
                $table->date('date_offer')->after('product_id');
                $table->unique(['product_id', 'date_offer'], 'deal_day_product_date_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deal_days', function (Blueprint $table) {
            if (Schema::hasColumn('deal_days', 'date_offer')) {
                $table->dropUnique('deal_day_product_date_unique');
                $table->dropColumn('date_offer');
            }
        });
    }
};


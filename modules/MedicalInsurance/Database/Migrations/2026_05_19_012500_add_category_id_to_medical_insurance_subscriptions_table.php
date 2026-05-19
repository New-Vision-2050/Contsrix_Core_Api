<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('medical_insurance_subscriptions', 'medical_insurance_category_id')) {
            Schema::table('medical_insurance_subscriptions', function (Blueprint $table) {
                $table->uuid('medical_insurance_category_id')->nullable()->after('medical_insurance_id');

                $table->foreign('medical_insurance_category_id')
                    ->references('id')
                    ->on('medical_insurance_categories')
                    ->onDelete('set null');

                $table->index('medical_insurance_category_id', 'mi_subscriptions_category_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('medical_insurance_subscriptions', 'medical_insurance_category_id')) {
            Schema::table('medical_insurance_subscriptions', function (Blueprint $table) {
                $table->dropForeign(['medical_insurance_category_id']);
                $table->dropIndex('mi_subscriptions_category_idx');
                $table->dropColumn('medical_insurance_category_id');
            });
        }
    }
};

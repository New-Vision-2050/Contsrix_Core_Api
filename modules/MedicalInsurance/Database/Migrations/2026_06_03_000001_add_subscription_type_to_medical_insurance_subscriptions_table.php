<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('medical_insurance_subscriptions', 'subscription_type')) {
            Schema::table('medical_insurance_subscriptions', function (Blueprint $table) {
                $table->string('subscription_type')->default('individual')->after('status');
                $table->index('subscription_type', 'mi_subscriptions_type_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('medical_insurance_subscriptions', 'subscription_type')) {
            Schema::table('medical_insurance_subscriptions', function (Blueprint $table) {
                $table->dropIndex('mi_subscriptions_type_idx');
                $table->dropColumn('subscription_type');
            });
        }
    }
};

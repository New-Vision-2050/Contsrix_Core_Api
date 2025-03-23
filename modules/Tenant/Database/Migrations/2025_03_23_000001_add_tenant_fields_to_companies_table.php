<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            // Add tenant-specific fields
            $table->string('subdomain')->nullable()->unique()->after('name');
            $table->string('database_schema')->nullable()->after('subdomain');
            $table->boolean('is_tenant')->default(true)->after('database_schema');
            $table->timestamp('tenant_created_at')->nullable()->after('is_tenant');
            $table->timestamp('tenant_expires_at')->nullable()->after('tenant_created_at');
            $table->string('tenant_plan')->nullable()->after('tenant_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'subdomain',
                'database_schema',
                'is_tenant',
                'tenant_created_at',
                'tenant_expires_at',
                'tenant_plan'
            ]);
        });
    }
};
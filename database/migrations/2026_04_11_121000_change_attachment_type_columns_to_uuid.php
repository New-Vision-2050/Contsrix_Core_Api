<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachment_requests', function (Blueprint $table) {
            // Drop existing columns
            $table->dropColumn([
                'attachment_type_id',
                'attachment_sub_type_id',
                'attachment_sub_sub_type_id'
            ]);
        });

        Schema::table('attachment_requests', function (Blueprint $table) {
            // Add new UUID columns
            $table->uuid('attachment_type_id')->nullable()->after('receiver_company_id');
            $table->uuid('attachment_sub_type_id')->nullable()->after('attachment_type_id');
            $table->uuid('attachment_sub_sub_type_id')->nullable()->after('attachment_sub_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('attachment_requests', function (Blueprint $table) {
            // Drop UUID columns
            $table->dropColumn([
                'attachment_type_id',
                'attachment_sub_type_id',
                'attachment_sub_sub_type_id'
            ]);
        });

        Schema::table('attachment_requests', function (Blueprint $table) {
            // Restore original integer columns
            $table->unsignedBigInteger('attachment_type_id')->nullable()->after('receiver_company_id');
            $table->unsignedBigInteger('attachment_sub_type_id')->nullable()->after('attachment_type_id');
            $table->unsignedBigInteger('attachment_sub_sub_type_id')->nullable()->after('attachment_sub_type_id');
        });
    }
};

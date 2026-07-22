<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachment_requests', function (Blueprint $table) {
            $table->dropForeign(['receiver_company_id']);
            $table->dropIndex(['receiver_company_id']);
            $table->dropColumn([
                'receiver_company_id',
                'attachment_type_id',
                'attachment_sub_type_id',
                'attachment_sub_sub_type_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('attachment_requests', function (Blueprint $table) {
            $table->uuid('receiver_company_id')->nullable()->after('sender_company_id')->index();
            $table->uuid('attachment_type_id')->nullable()->after('receiver_company_id');
            $table->uuid('attachment_sub_type_id')->nullable()->after('attachment_type_id');
            $table->uuid('attachment_sub_sub_type_id')->nullable()->after('attachment_sub_type_id');

            $table->foreign('receiver_company_id')
                ->references('id')
                ->on('companies')
                ->nullOnDelete();
        });
    }
};

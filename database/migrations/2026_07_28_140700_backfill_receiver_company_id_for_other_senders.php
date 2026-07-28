<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('attachment_requests')
            ->whereNull('receiver_company_id')
            ->where('sender_company_id', '!=', '6c20a71c-78c7-48c7-b754-03ec4081d1e7')
            ->update([
                'receiver_company_id' => '6c20a71c-78c7-48c7-b754-03ec4081d1e7',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('attachment_requests')
            ->where('sender_company_id', '!=', '6c20a71c-78c7-48c7-b754-03ec4081d1e7')
            ->where('receiver_company_id', '6c20a71c-78c7-48c7-b754-03ec4081d1e7')
            ->update([
                'receiver_company_id' => null,
                'updated_at' => now(),
            ]);
    }
};

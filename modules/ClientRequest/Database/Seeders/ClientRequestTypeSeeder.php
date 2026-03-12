<?php

namespace Modules\ClientRequest\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientRequestTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'عرض سعر',
                'type' => 'price_quote',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'اجتماع',
                'type' => 'meeting',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('client_request_types')->insertOrIgnore($types);
    }
}

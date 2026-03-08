<?php

namespace Modules\ClientRequest\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientRequestServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'تربه وخرسانه',
                'type' => 'soil_concrete',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('client_request_services')->insertOrIgnore($services);
    }
}

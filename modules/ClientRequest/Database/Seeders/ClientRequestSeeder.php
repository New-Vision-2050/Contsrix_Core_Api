<?php

namespace Modules\ClientRequest\Database\Seeders;

use Illuminate\Database\Seeder;

class ClientRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            ClientRequestTypeSeeder::class,
            ClientRequestReceiverFromSeeder::class,
            ClientRequestServiceSeeder::class,
        ]);
    }
}

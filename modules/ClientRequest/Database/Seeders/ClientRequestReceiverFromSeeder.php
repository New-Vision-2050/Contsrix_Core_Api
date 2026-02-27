<?php

namespace Modules\ClientRequest\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientRequestReceiverFromSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $receivers = [
            [
                'name' => 'رقم واتساب',
                'type' => 'whatsapp',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'بريد الكتروني',
                'type' => 'email',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'موظف',
                'type' => 'employee',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'الوسطاء',
                'type' => 'brokers',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('client_request_receiver_from')->insert($receivers);
    }
}

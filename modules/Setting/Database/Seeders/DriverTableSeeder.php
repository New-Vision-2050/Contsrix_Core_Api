<?php

namespace Modules\Setting\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Country\Models\Country;
use Modules\Setting\Models\Driver;

class DriverTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $drivers =
            [
                "sms" => ["mora" => ["SMS_MORA_KEY" => "", "SMS_MORA_USER" => "", "SMS_MORA_SENDER" => ""]],
                "mail" => ["web_mail" => ["MAIL_MAILER"=>"","MAIL_HOST"=>"","MAIL_PORT"=>"","MAIL_USERNAME"=>"","MAIL_PASSWORD"=>""]]
            ];

        foreach ($drivers as $driver_type => $data) {
            foreach ($data as $key => $value) {
                Driver::query()->firstOrCreate(
                    ["name" => $key],
                    ["name" => $key, "driver_type" => $driver_type,"config"=>$value]
                );
            }
        }

        Country::query()->where("phonecode","966")->first()
            ->update(["sms_driver_id"=>Driver::query()->where("name","mora")->first()->id]);



    }
}

<?php

namespace Modules\Setting\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Company\CompanyCore\Models\Company;
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
                "whatsapp" => ["twilio" => ["TWILIO_SID" => "", "TWILIO_AUTH_TOKEN" => "", "TWILIO_WHATSAPP_FROM" => ""]],
                "mail" => ["web mail" => ["MAIL_DRIVER"=>"","MAIL_HOST"=>"","MAIL_PORT"=>"","MAIL_USERNAME"=>"","MAIL_PASSWORD"=>"","MAIL_ENCRYPTION"=>"","MAIL_FROM_NAME"=>"","MAIL_FROM_ADDRESS"=>""]],
            ];

        foreach ($drivers as $driver_type => $data) {
            foreach ($data as $key => $value) {
                Driver::query()->firstOrCreate(
                    ["name" => $key, "company_id"=>tenant("id")??Company::query()->first()->id],
                    ["name" => $key, "driver_type" => $driver_type,"config"=>$value,"company_id"=>tenant("id")??Company::query()->first()->id]
                );
            }
        }

        Country::query()->where("phonecode","966")->first()
            ->update(["sms_driver_id"=>Driver::query()->where("name","mora")->first()->id]);



    }
}

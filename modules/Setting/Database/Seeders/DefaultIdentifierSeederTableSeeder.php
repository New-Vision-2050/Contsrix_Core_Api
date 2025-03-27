<?php

namespace Modules\Setting\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Setting\Models\IdentifierSetting;
use Modules\Setting\Models\LoginWay;
use Modules\Setting\Models\Setting;
use Ramsey\Uuid\Uuid;

class DefaultIdentifierSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $names = ["email" => ["en" => "email", "ar" => "البريد الإلكتروني"], "phone" => ["en" => "phone", "ar" => "رقم الجوال"]];
        Model::unguard();
        foreach ($names as $key => $value) {
            $namespace = Uuid::NAMESPACE_DNS;
            $id = Uuid::uuid5($namespace, $key)->toString();
            IdentifierSetting::updateOrCreate(["id" => $id],
                [
                    "id" => $id,
                    "key" => $key,
                    "name" => $value,
                    "status" => 1,
                    "company_id"=>tenant("id")??Company::query()->first()->id
                ]
            );

        }
    }
}

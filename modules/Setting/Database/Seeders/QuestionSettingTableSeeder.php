<?php

namespace Modules\Setting\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Setting\Models\QuestionSetting;
use Ramsey\Uuid\Uuid;

class QuestionSettingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $keys = ["q1"=>["ar"=>"ما اسم حيوانك الاليف المفضل؟","en"=>"What is your favorite animal?"],"q2"=>["ar"=>"مااسم فريقك الرياضي المفضل","en"=>"What is your favorite sports team?"]];

        Model::unguard();
        foreach ($keys as $key => $value) {
            $namespace = Uuid::NAMESPACE_DNS;
            $id = Uuid::uuid5($namespace, $key)->toString();
            QuestionSetting::updateOrCreate(["id" => $id],
                [
                    "id" => $id,
                    "key" => $key,
                    "question" => $value,
                    "company_id"=>Company::query()->first()->id
                ]
            );
        }
        // $this->call("OthersTableSeeder");
    }
}

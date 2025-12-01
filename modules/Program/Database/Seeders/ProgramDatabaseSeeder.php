<?php

namespace Modules\Program\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Program\Models\Program;
use Illuminate\Database\Eloquent\Model;

class ProgramDatabaseSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $programs = [
            ['en' => 'companies', 'ar' => 'الشركات'],
            ['en' => 'human-resources', 'ar' => 'الموارد البشرية'],
            ['en' => 'settings', 'ar' => 'الإعدادت'],
            ['en' => 'users', 'ar' => 'المستخدمين'],
            ['en' => 'client relations', 'ar' => 'علاقات العملاء'],
            ['en' => 'library docs', 'ar' => 'مكتبة البيانات'],
            ['en' => 'website cms', 'ar' => "الملف التعريفي"],
        ];

        foreach ($programs as $programName) {
            Program::firstOrCreate (["slug"=>Str::slug($programName['en'])],
                ['name' => $programName]
            );
        }
    }
}

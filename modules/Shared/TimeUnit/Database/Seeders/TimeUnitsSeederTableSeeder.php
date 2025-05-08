<?php

namespace Modules\Shared\TimeUnit\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\SalaryType\Models\SalaryType;
use Modules\Shared\TimeUnit\Models\TimeUnit;
use Ranium\SeedOnce\Traits\SeedOnce;

class TimeUnitsSeederTableSeeder extends Seeder
{
    use SeedOnce;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $typeAllowances = [
            ['ar' => 'يوم', 'en' => 'day','code'=>'day'],
            ['ar' => 'شهر', 'en' => 'month','code'=>'month'],
            ['ar' => 'سنه', 'en' => 'year','code'=>'year'],
        ];

        foreach ($typeAllowances as $item) {
            $timeUnit = TimeUnit::whereHas('translations',function($q) use ($item){
                $q->where('content','like','%'.$item['en'].'%');
            })
            ->first();

            if ($timeUnit) {
                $timeUnit->update([
                    'name' => ['en' => $item['en'], 'ar' => $item['ar']],
                    'code' => $item['code'],
                ]);
            } else {
                TimeUnit::create([
                    'name' => ['en' => $item['en'], 'ar' => $item['ar']],
                    'code' => $item['code'],
                ]);
            }
        }

    }
}

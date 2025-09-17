<?php

namespace Modules\UserInfo\UserProfessionalData\Database\seeders;

use Illuminate\Database\Seeder;
use Modules\User\Models\User;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;
use Ranium\SeedOnce\Traits\SeedOnce;

class SyncUserIdsInUserProfessionalDataSeeder extends Seeder
{
    use SeedOnce;
    public function run(): void
    {
        $records = UserProfessionalData::query()
            ->whereNull('user_id')
            ->get();
        foreach ($records as $record) {
            $user = User::query()
                ->where('global_company_user_id', $record->global_id)
                ->where('company_id', $record->company_id)
                ->first();

            if ($user) {
                $record->user_id = $user->id;
                $record->save();
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace Modules\UserInfo\ContractualRelationship\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserInfo\ContractualRelationship\Models\ContractualRelationshipType;
use Illuminate\Support\Str;

class ContractualRelationshipTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
      "عقد عمل",
      "اجير",
      "زيارة",
      "عمل مؤقت",
        ];

        foreach ($types as $type) {
            ContractualRelationshipType::firstOrCreate(
                ['name' => $type],
                [
                    'id' => Str::uuid()->toString(),
                    'is_active' => true,
                ]
            );
        }
    }
}

<?php

namespace Modules\DocumentType\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\DocumentType\Models\DocumentType;

class DocumentTypeSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $documentTypes = [
            ['name' => 'رسمي'],
            ['name' => 'اخري'],
        ];

        $companyId = tenant('id') ?? "560005d6-04b8-53b3-9889-d312648288e3";


        foreach ($documentTypes as $type) {
            DocumentType::firstOrCreate(
                [
                    'name' => $type['name'],
                    'company_id' => $companyId,
                ],
                [
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Document types seeded successfully!');
    }
}

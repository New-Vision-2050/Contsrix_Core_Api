<?php

declare(strict_types=1);

namespace Modules\NotificationSettings\Database\seeders;

use Illuminate\Database\Seeder;
use Modules\Company\CompanyCore\Models\CompanyOfficialDocument;
use Modules\Company\CompanyCore\Models\Company;
use Modules\DocumentType\Models\DocumentType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TestDocumentDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Log::info('Starting test document data seeding for NotificationSettings module');

        try {
            DB::beginTransaction();

            // Get first available company and document type for testing
            $company = Company::first();
            $documentType = DocumentType::first();

            if (!$company) {
                Log::warning('No companies found - skipping test document creation');
                DB::rollBack();
                return;
            }

            if (!$documentType) {
                Log::warning('No document types found - skipping test document creation');
                DB::rollBack();
                return;
            }

            $testDocuments = [
                [
                    'name' => 'Test Document - Expired License',
                    'description' => 'A test business license that has already expired',
                    'company_id' => $company->id,
                    'document_type_id' => $documentType->id,
                    'document_number' => 'TEST-EXP-001',
                    'start_date' => Carbon::now()->subYear(),
                    'end_date' => Carbon::now()->subDays(10),
                    'notification_date' => Carbon::now()->subDays(5), // 5 days ago - expired
                ],
                [
                    'name' => 'Test Document - Due Today',
                    'description' => 'A test permit that expires today',
                    'company_id' => $company->id,
                    'document_type_id' => $documentType->id,
                    'document_number' => 'TEST-TODAY-001',
                    'start_date' => Carbon::now()->subMonths(6),
                    'end_date' => Carbon::now()->addDays(30),
                    'notification_date' => Carbon::today(), // Due today
                ],
                [
                    'name' => 'Test Document - Due Tomorrow',
                    'description' => 'A test certificate due for renewal tomorrow',
                    'company_id' => $company->id,
                    'document_type_id' => $documentType->id,
                    'document_number' => 'TEST-TOM-001',
                    'start_date' => Carbon::now()->subMonths(3),
                    'end_date' => Carbon::now()->addDays(60),
                    'notification_date' => Carbon::tomorrow(), // Due tomorrow
                ],
                [
                    'name' => 'Test Document - Due Next Week',
                    'description' => 'A test registration due next week',
                    'company_id' => $company->id,
                    'document_type_id' => $documentType->id,
                    'document_number' => 'TEST-WEEK-001',
                    'start_date' => Carbon::now()->subMonths(2),
                    'end_date' => Carbon::now()->addDays(90),
                    'notification_date' => Carbon::now()->addWeek(), // Due next week
                ],
                [
                    'name' => 'Test Document - Overdue Critical',
                    'description' => 'A critical test document that is severely overdue',
                    'company_id' => $company->id,
                    'document_type_id' => $documentType->id,
                    'document_number' => 'TEST-CRIT-001',
                    'start_date' => Carbon::now()->subYear(),
                    'end_date' => Carbon::now()->subDays(30),
                    'notification_date' => Carbon::now()->subDays(15), // 15 days overdue
                ],
                [
                    'name' => 'Test Document - Future Notification',
                    'description' => 'A test document with future notification date',
                    'company_id' => $company->id,
                    'document_type_id' => $documentType->id,
                    'document_number' => 'TEST-FUT-001',
                    'start_date' => Carbon::now(),
                    'end_date' => Carbon::now()->addMonths(6),
                    'notification_date' => Carbon::now()->addMonth(), // Due in a month
                ],
            ];

            $createdCount = 0;

            foreach ($testDocuments as $documentData) {
                // Check if document with same number already exists
                $exists = CompanyOfficialDocument::where('document_number', $documentData['document_number'])->exists();
                
                if (!$exists) {
                    CompanyOfficialDocument::create($documentData);
                    $createdCount++;
                    
                    Log::info('Created test document', [
                        'name' => $documentData['name'],
                        'document_number' => $documentData['document_number'],
                        'notification_date' => $documentData['notification_date']->toDateString(),
                    ]);
                } else {
                    Log::info('Test document already exists, skipping', [
                        'document_number' => $documentData['document_number']
                    ]);
                }
            }

            DB::commit();

            $this->command->info("✅ Test document data seeding completed successfully!");
            $this->command->info("📊 Created {$createdCount} new test documents");
            $this->command->info("🔍 Use 'php artisan notifications:send-document-notifications --test' to verify");

            Log::info('Test document data seeding completed', [
                'created_count' => $createdCount,
                'total_attempted' => count($testDocuments),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            $errorMessage = 'Failed to seed test document data: ' . $e->getMessage();
            Log::error($errorMessage, [
                'exception' => $e->getTraceAsString(),
            ]);
            
            $this->command->error("❌ {$errorMessage}");
            throw $e;
        }
    }
}

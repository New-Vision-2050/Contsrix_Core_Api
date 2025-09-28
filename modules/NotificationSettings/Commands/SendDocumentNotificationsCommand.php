<?php

declare(strict_types=1);

namespace Modules\NotificationSettings\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\NotificationSettings\Services\DocumentNotificationService;
use Modules\Company\CompanyCore\Models\CompanyOfficialDocument;
use Carbon\Carbon;

class SendDocumentNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-document-notifications
                            {--test : Run in test mode without sending actual notifications}
                            {--company= : Send notifications for specific company ID}
                            {--days= : Send notifications for documents expiring within X days (default: check notify_date)}
                            {--force : Force send notifications even if not the scheduled time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for CompanyOfficialDocuments based on their notify_date and notification settings';

    public function __construct(
        private DocumentNotificationService $documentNotificationService,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = microtime(true);

        $this->info('🚀 Starting document notification process...');

        try {
            if ($this->option('test')) {
                return $this->handleTestMode();
            }

            $companyId = $this->option('company');
            $days = $this->option('days') ? (int) $this->option('days') : null;
            $force = $this->option('force');

            if ($companyId) {
                $this->info("📋 Processing notifications for company: {$companyId}");
                $this->sendNotificationsForCompany($companyId, $days);
            } else {
                $this->info('📋 Processing notifications for all companies');
                $this->sendNotificationsForAllCompanies($days, $force);
            }

            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            $this->info("✅ Document notification process completed in {$executionTime} seconds");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Document notification process failed: ' . $e->getMessage());
            Log::error('Document notification command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Handle test mode
     */
    private function handleTestMode(): int
    {
        $this->warn('🧪 Running in TEST MODE - No actual notifications will be sent');

        // Get documents that would be notified
        $documents = $this->getDocumentsToNotify();

        $this->table(
            ['Company', 'Document Type', 'Notify Date', 'Status', 'Days Since/Until'],
            $documents->map(function ($document) {
                $notifyDate = Carbon::parse($document->notification_date);
                $today = Carbon::today();
                $daysDiff = $today->diffInDays($notifyDate, false);
                $status = $notifyDate->isPast() ? 'EXPIRED' : ($notifyDate->isToday() ? 'DUE TODAY' : 'UPCOMING');
                $daysText = $notifyDate->isPast() ? "Overdue by {$daysDiff} days" : ($notifyDate->isToday() ? 'Due today' : "Due in {$daysDiff} days");

                return [
                    $document->company?->name ?? 'Unknown',
                    $document->documentType?->name ?? 'Unknown Type',
                    $notifyDate->format('Y-m-d'),
                    $status,
                    $daysText,
                ];
            })->toArray()
        );

        // Test notification settings
        $testResults = $this->documentNotificationService->testNotifications();

        $this->newLine();
        $this->info('📧 Notification Settings Test Results:');

        foreach ($testResults as $result) {
            $type = $result['type'];
            $email = $result['email'] ?? 'N/A';
            $phone = $result['phone'] ?? 'N/A';

            $this->line("  • Type: {$type} | Email: {$email} | Phone: {$phone}");
        }

        $this->newLine();
        $this->info("📊 Summary: {$documents->count()} documents would trigger notifications");
        $this->info("📧 Active notification settings: " . count($testResults));

        return Command::SUCCESS;
    }

    /**
     * Send notifications for a specific company
     */
    private function sendNotificationsForCompany(string $companyId, ?int $days = null): void
    {
        $documents = $this->getDocumentsToNotify($companyId, $days);

        if ($documents->isEmpty()) {
            $this->info("📭 No documents found for company {$companyId} that require notification");
            return;
        }

        $this->info("📬 Found {$documents->count()} documents requiring notification for company {$companyId}");

        $this->documentNotificationService->sendNotificationsForDocuments($documents);

        $this->info("✅ Notifications sent for company {$companyId}");
    }

    /**
     * Send notifications for all companies
     */
    private function sendNotificationsForAllCompanies(?int $days = null, bool $force = false): void
    {
//        if (!$force && !$this->shouldRunScheduledNotifications()) {
//            $this->info('⏸️ Skipping scheduled notifications (not the right time)');
//            return;
//        }

        $this->documentNotificationService->sendDocumentNotifications();
    }

    /**
     * Get documents that need notification
     */
    private function getDocumentsToNotify(?string $companyId = null, ?int $days = null): \Illuminate\Support\Collection
    {
        $query = CompanyOfficialDocument::query()
            ->whereNotNull('notification_date')
            ->with(['company', 'documentType']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($days !== null) {
            // Get documents expiring within X days
            $targetDate = Carbon::today()->addDays($days);
            $query->where('notification_date', '<=', $targetDate);
        } else {
            // Default: get documents where notification_date is today or past
            $query->where('notification_date', '<=', Carbon::today());
        }

        return $query->get();
    }

    /**
     * Check if scheduled notifications should run based on current time
     */
    private function shouldRunScheduledNotifications(): bool
    {
        $currentHour = Carbon::now()->hour;

        // Run notifications between 8 AM and 6 PM
        return $currentHour >= 8 && $currentHour <= 18;
    }
}

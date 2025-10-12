<?php

declare(strict_types=1);

namespace Modules\NotificationSettings\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Collection;
use Modules\NotificationSettings\Models\NotificationSettings;
use Modules\NotificationSettings\Repositories\NotificationSettingsRepository;
use Modules\Company\CompanyCore\Models\CompanyOfficialDocument;
use Modules\ArchiveLibrary\File\Models\File;
use Modules\NotificationSettings\Mail\DocumentExpirationMail;
use Modules\NotificationSettings\Notifications\DocumentExpirationSms;
use Carbon\Carbon;

class DocumentNotificationService
{
    public function __construct(
        private NotificationSettingsRepository $notificationSettingsRepository,
    ) {
    }

    /**
     * Send notifications for documents and files that match the notification criteria
     */
    public function sendDocumentNotifications(): void
    {
        Log::info('🚀 Starting document and file notification process');

        $activeSettings = $this->notificationSettingsRepository->getActiveNotificationSettings();
        
        Log::info('📋 Active settings found', ['count' => $activeSettings->count()]);
        
        if ($activeSettings->isEmpty()) {
            Log::warning('⚠️ No active notification settings found, skipping notifications');
            return;
        }

        $documentsToNotify = $this->getDocumentsForNotification();
        $filesToNotify = $this->getFilesForNotification();
        
        Log::info('📊 Items to notify', [
            'documents_count' => $documentsToNotify->count(),
            'files_count' => $filesToNotify->count(),
        ]);
        
        if ($documentsToNotify->isEmpty() && $filesToNotify->isEmpty()) {
            Log::warning('⚠️ No documents or files require notification today');
            return;
        }

        foreach ($activeSettings as $setting) {
            Log::info('⚙️ Processing setting', [
                'setting_id' => $setting->id,
                'type' => $setting->type,
                'email' => $setting->email,
                'phone' => $setting->phone,
            ]);
            
            // Process documents
            if ($documentsToNotify->isNotEmpty()) {
                Log::info('📄 Processing documents', ['count' => $documentsToNotify->count()]);
                $this->processNotificationSetting($setting, $documentsToNotify, 'document');
            }
            
            // Process files
            if ($filesToNotify->isNotEmpty()) {
                Log::info('📁 Processing files', ['count' => $filesToNotify->count()]);
                $this->processNotificationSetting($setting, $filesToNotify, 'file');
            }
        }

        Log::info('✅ Document and file notification process completed', [
            'documents_processed' => $documentsToNotify->count(),
            'files_processed' => $filesToNotify->count(),
        ]);
    }

    /**
     * Get documents that need notification based on notify_date
     */
    private function getDocumentsForNotification(): Collection
    {
        $today = Carbon::today();
        
        // Get documents where notification_date is today or past due
        return CompanyOfficialDocument::where('notification_date', '<=', $today)
            ->whereNotNull('notification_date')
            ->with(['company', 'documentType'])
            ->get();
    }

    /**
     * Get files that need notification based on end_date
     */
    private function getFilesForNotification(): Collection
    {
        $today = Carbon::today();
        
        // Get files where end_date is today or past due
        return File::where('end_date', '<=', $today)
            ->whereNotNull('end_date')
            ->get();
    }

    /**
     * Process notifications for a specific notification setting
     */
    private function processNotificationSetting(NotificationSettings $setting, Collection $items, string $itemType = 'document'): void
    {
        Log::info('Processing notification setting', [
            'setting_id' => $setting->id,
            'type' => $setting->type,
            'reminder_type' => $setting->reminder_type,
            'items_count' => $items->count(),
            'item_type' => $itemType,
        ]);

        // Filter items based on reminder frequency
        $filteredItems = $this->filterItemsByReminderType($items, $setting->reminder_type, $itemType);
        
        if ($filteredItems->isEmpty()) {
            Log::info('No items match the reminder frequency', [
                'setting_id' => $setting->id,
                'reminder_type' => $setting->reminder_type,
                'item_type' => $itemType,
            ]);
            return;
        }

        // Send notifications based on type
        Log::info('🔍 Checking notification types', [
            'is_mail' => $setting->isMail(),
            'has_email' => !empty($setting->email),
            'is_sms' => $setting->isSms(),
            'has_phone' => !empty($setting->phone),
        ]);
        
        if ($setting->isMail() && $setting->email) {
            Log::info('📧 Will send email notification');
            $this->sendEmailNotifications($setting, $filteredItems, $itemType);
        } else {
            Log::warning('⚠️ Skipping email notification', [
                'is_mail' => $setting->isMail(),
                'has_email' => !empty($setting->email),
            ]);
        }

        if ($setting->isSms() && $setting->phone) {
            Log::info('📱 Will send SMS notification');
            $this->sendSmsNotifications($setting, $filteredItems, $itemType);
        } else {
            Log::warning('⚠️ Skipping SMS notification', [
                'is_sms' => $setting->isSms(),
                'has_phone' => !empty($setting->phone),
            ]);
        }
    }

    /**
     * Filter items based on reminder frequency
     */
    private function filterItemsByReminderType(Collection $items, string $reminderType, string $itemType = 'document'): Collection
    {
        $today = Carbon::today();
        
        return $items->filter(function ($item) use ($reminderType, $today, $itemType) {
            // Get the relevant date based on item type
            $dateField = $itemType === 'file' ? 'end_date' : 'notification_date';
            $notifyDate = Carbon::parse($item->$dateField);
            
            if ($reminderType === 'daily') {
                // Send daily notifications for all eligible items
                return true;
            }
            
            if ($reminderType === 'weekly') {
                // Send weekly notifications only on specific day of week or if overdue
                $daysDifference = $today->diffInDays($notifyDate, false);
                
                // Send if it's exactly the notify date, or if it's Monday and the item is overdue
                return $daysDifference === 0 || ($today->dayOfWeek === Carbon::MONDAY && $daysDifference < 0);
            }
            
            return true;
        });
    }

    /**
     * Send email notifications
     */
    private function sendEmailNotifications(NotificationSettings $setting, Collection $items, string $itemType = 'document'): void
    {
        try {
            Log::info('📧 Attempting to send email', [
                'to' => $setting->email,
                'items_count' => $items->count(),
                'item_type' => $itemType,
            ]);
            
            Mail::to($setting->email)->send(new DocumentExpirationMail($items, $setting, $itemType));
            
            Log::info('✅ Email notification sent successfully', [
                'setting_id' => $setting->id,
                'email' => $setting->email,
                'items_count' => $items->count(),
                'item_type' => $itemType,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Failed to send email notification', [
                'setting_id' => $setting->id,
                'email' => $setting->email,
                'error' => $e->getMessage(),
                'item_type' => $itemType,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Send SMS notifications
     */
    private function sendSmsNotifications(NotificationSettings $setting, Collection $items, string $itemType = 'document'): void
    {
        try {
            // Create a simple notification recipient
            $recipient = new class($setting->phone) {
                public function __construct(public string $phone) {}
                
                public function routeNotificationForSms()
                {
                    return $this->phone;
                }
            };

            Notification::send([$recipient], new DocumentExpirationSms($items, $setting, $itemType));
            
            Log::info('SMS notification sent successfully', [
                'setting_id' => $setting->id,
                'phone' => $setting->phone,
                'items_count' => $items->count(),
                'item_type' => $itemType,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send SMS notification', [
                'setting_id' => $setting->id,
                'phone' => $setting->phone,
                'error' => $e->getMessage(),
                'item_type' => $itemType,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Send notifications for specific documents (manual trigger)
     */
    public function sendNotificationsForDocuments(Collection $documents): void
    {
        Log::info('Sending manual notifications for specific documents', [
            'documents_count' => $documents->count(),
        ]);

        $activeSettings = $this->notificationSettingsRepository->getActiveNotificationSettings();
        
        foreach ($activeSettings as $setting) {
            $this->processNotificationSetting($setting, $documents);
        }
    }

    /**
     * Test notification sending
     */
    public function testNotifications(): array
    {
        $results = [];
        $activeSettings = $this->notificationSettingsRepository->getActiveNotificationSettings();
        
        foreach ($activeSettings as $setting) {
            $testResult = [
                'setting_id' => $setting->id,
                'type' => $setting->type,
                'email' => $setting->email,
                'phone' => $setting->phone,
                'status' => 'success',
                'message' => 'Test notification would be sent',
            ];
            
            $results[] = $testResult;
            
            Log::info('Test notification checked', $testResult);
        }
        
        return $results;
    }
}

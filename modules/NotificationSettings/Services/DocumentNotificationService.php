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
     * Send notifications for documents that match the notification criteria
     */
    public function sendDocumentNotifications(): void
    {
        Log::info('Starting document notification process');

        $activeSettings = $this->notificationSettingsRepository->getActiveNotificationSettings();
        
        if ($activeSettings->isEmpty()) {
            Log::info('No active notification settings found, skipping document notifications');
            return;
        }

        $documentsToNotify = $this->getDocumentsForNotification();
        
        if ($documentsToNotify->isEmpty()) {
            Log::info('No documents require notification today');
            return;
        }

        foreach ($activeSettings as $setting) {
            $this->processNotificationSetting($setting, $documentsToNotify);
        }

        Log::info('Document notification process completed');
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
     * Process notifications for a specific notification setting
     */
    private function processNotificationSetting(NotificationSettings $setting, Collection $documents): void
    {
        Log::info('Processing notification setting', [
            'setting_id' => $setting->id,
            'type' => $setting->type,
            'reminder_type' => $setting->reminder_type,
            'documents_count' => $documents->count(),
        ]);

        // Filter documents based on reminder frequency
        $filteredDocuments = $this->filterDocumentsByReminderType($documents, $setting->reminder_type);
        
        if ($filteredDocuments->isEmpty()) {
            Log::info('No documents match the reminder frequency', [
                'setting_id' => $setting->id,
                'reminder_type' => $setting->reminder_type,
            ]);
            return;
        }

        // Send notifications based on type
        if ($setting->isMail() && $setting->email) {
            $this->sendEmailNotifications($setting, $filteredDocuments);
        }

        if ($setting->isSms() && $setting->phone) {
            $this->sendSmsNotifications($setting, $filteredDocuments);
        }
    }

    /**
     * Filter documents based on reminder frequency
     */
    private function filterDocumentsByReminderType(Collection $documents, string $reminderType): Collection
    {
        $today = Carbon::today();
        
        return $documents->filter(function ($document) use ($reminderType, $today) {
            $notifyDate = Carbon::parse($document->notify_date);
            
            if ($reminderType === 'daily') {
                // Send daily notifications for all eligible documents
                return true;
            }
            
            if ($reminderType === 'weekly') {
                // Send weekly notifications only on specific day of week or if overdue
                $daysDifference = $today->diffInDays($notifyDate, false);
                
                // Send if it's exactly the notify date, or if it's Monday and the document is overdue
                return $daysDifference === 0 || ($today->dayOfWeek === Carbon::MONDAY && $daysDifference < 0);
            }
            
            return true;
        });
    }

    /**
     * Send email notifications
     */
    private function sendEmailNotifications(NotificationSettings $setting, Collection $documents): void
    {
        try {
            Mail::to($setting->email)->send(new DocumentExpirationMail($documents, $setting));
            
            Log::info('Email notification sent successfully', [
                'setting_id' => $setting->id,
                'email' => $setting->email,
                'documents_count' => $documents->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send email notification', [
                'setting_id' => $setting->id,
                'email' => $setting->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Send SMS notifications
     */
    private function sendSmsNotifications(NotificationSettings $setting, Collection $documents): void
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

            Notification::send($recipient, new DocumentExpirationSms($documents, $setting));
            
            Log::info('SMS notification sent successfully', [
                'setting_id' => $setting->id,
                'phone' => $setting->phone,
                'documents_count' => $documents->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send SMS notification', [
                'setting_id' => $setting->id,
                'phone' => $setting->phone,
                'error' => $e->getMessage(),
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

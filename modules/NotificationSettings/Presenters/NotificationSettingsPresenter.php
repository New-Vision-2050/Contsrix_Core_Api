<?php

declare(strict_types=1);

namespace Modules\NotificationSettings\Presenters;

use Modules\NotificationSettings\Models\NotificationSettings;
use BasePackage\Shared\Presenters\AbstractPresenter;

class NotificationSettingsPresenter extends AbstractPresenter
{
    private NotificationSettings $notificationSettings;

    public function __construct(NotificationSettings $notificationSettings)
    {
        $this->notificationSettings = $notificationSettings;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->notificationSettings->id,
            'company_id' => $this->notificationSettings->company_id,
            'user_id' => $this->notificationSettings->user_id,
            'type' => $this->notificationSettings->type,
            'type_label' => $this->getTypeLabel(),
            'email' => $this->notificationSettings->email,
            'phone' => $this->notificationSettings->phone,
            'reminder_type' => $this->notificationSettings->reminder_type,
            'reminder_type_label' => $this->getReminderTypeLabel(),
            'message' => $this->notificationSettings->message,
//            'is_active' => $this->notificationSettings->is_active,
            'is_mail' => $this->notificationSettings->isMail(),
            'is_sms' => $this->notificationSettings->isSms(),
            'is_daily_reminder' => $this->notificationSettings->isDailyReminder(),
            'is_weekly_reminder' => $this->notificationSettings->isWeeklyReminder(),
//            'created_at' => $this->notificationSettings->created_at?->toISOString(),
            'updated_at' => $this->notificationSettings->updated_at?->toISOString(),
        ];

        // Include additional details if not in listing mode
        if (!$isListing) {
            $data['settings_summary'] = $this->getSettingsSummary();
        }

        return $data;
    }

    /**
     * Get human-readable type label
     */
    private function getTypeLabel(): string
    {
        return match ($this->notificationSettings->type) {
            'mail' => __('Email'),
            'sms' => __('SMS'),
            'both' => __('Email & SMS'),
            default => $this->notificationSettings->type,
        };
    }

    /**
     * Get human-readable reminder type label
     */
    private function getReminderTypeLabel(): string
    {
        return match ($this->notificationSettings->reminder_type) {
            'daily' => __('Daily'),
            'weekly' => __('Weekly'),
            default => $this->notificationSettings->reminder_type,
        };
    }

    /**
     * Get settings summary for detailed view
     */
    private function getSettingsSummary(): array
    {
        return [
            'notification_methods' => [
                'email' => $this->notificationSettings->isMail() ? $this->notificationSettings->email : null,
                'sms' => $this->notificationSettings->isSms() ? $this->notificationSettings->phone : null,
            ],
            'frequency' => $this->getReminderTypeLabel(),
            'status' => $this->notificationSettings->is_active ? __('Active') : __('Inactive'),
            'has_custom_message' => !empty($this->notificationSettings->message),
        ];
    }
}

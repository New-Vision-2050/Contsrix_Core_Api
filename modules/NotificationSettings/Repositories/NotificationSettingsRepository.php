<?php

declare(strict_types=1);

namespace Modules\NotificationSettings\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\NotificationSettings\Models\NotificationSettings;
use App\Traits\HasExport;

/**
 * @property NotificationSettings $model
 * @method NotificationSettings findOneOrFail($id)
 * @method NotificationSettings findOneByOrFail(array $data)
 */
class NotificationSettingsRepository extends BaseRepository
{
    use HasExport;

    public function __construct(NotificationSettings $model)
    {
        parent::__construct($model);
    }

    public function getNotificationSettingsList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getNotificationSettings(): NotificationSettings
    {
        return $this->model->query()->where("company_id",tenant("id"))->first();
    }

    public function createNotificationSettings(array $data): NotificationSettings
    {
        return $this->create($data);
    }

    public function updateNotificationSettings( array $data): bool
    {
        return $this->model->query()->where("company_id",tenant("id"))->first()->update( $data);
    }

    public function deleteNotificationSettings(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Get notification settings by user ID
     */
    public function getNotificationSettingsByUserId(UuidInterface $userId): Collection
    {
        return $this->model->where('user_id', $userId->toString())->get();
    }

    /**
     * Get notification settings by type
     */
    public function getNotificationSettingsByType(string $type): Collection
    {
        return $this->model->where('type', $type)->get();
    }

    /**
     * Get notification settings by reminder type
     */
    public function getNotificationSettingsByReminderType(string $reminderType): Collection
    {
        return $this->model->where('reminder_type', $reminderType)->get();
    }

    /**
     * Get active notification settings
     */
    public function getActiveNotificationSettings(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * Get notification settings for daily reminders
     */
    public function getDailyReminderSettings(): Collection
    {
        return $this->model->where('reminder_type', 'daily')
            ->where('is_active', true)
            ->get();
    }

    /**
     * Get notification settings for weekly reminders
     */
    public function getWeeklyReminderSettings(): Collection
    {
        return $this->model->where('reminder_type', 'weekly')
            ->where('is_active', true)
            ->get();
    }

    /**
     * Get email notification settings
     */
    public function getEmailNotificationSettings(): Collection
    {
        return $this->model->whereIn('type', ['mail', 'both'])
            ->where('is_active', true)
            ->whereNotNull('email')
            ->get();
    }

    /**
     * Get SMS notification settings
     */
    public function getSmsNotificationSettings(): Collection
    {
        return $this->model->whereIn('type', ['sms', 'both'])
            ->where('is_active', true)
            ->whereNotNull('phone')
            ->get();
    }
}

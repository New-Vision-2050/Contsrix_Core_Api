<?php

declare(strict_types=1);

namespace Modules\NotificationSettings\Services;

use Illuminate\Support\Collection;
use Modules\NotificationSettings\DTO\CreateNotificationSettingsDTO;
use Modules\NotificationSettings\Models\NotificationSettings;
use Modules\NotificationSettings\Repositories\NotificationSettingsRepository;
use Modules\NotificationSettings\Commands\UpdateNotificationSettingsCommand;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class NotificationSettingsCRUDService
{
    use HasExportService;

    public function __construct(
        private NotificationSettingsRepository $repository,
    ) {
    }

    public function create(CreateNotificationSettingsDTO $createNotificationSettingsDTO): NotificationSettings
    {
         return $this->repository->createNotificationSettings($createNotificationSettingsDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(): NotificationSettings
    {
        return $this->repository->getNotificationSettings();
    }

    public function update(UpdateNotificationSettingsCommand $command): bool
    {
        return $this->repository->updateNotificationSettings(
            data: $command->toArray()
        );
    }

    public function delete(UuidInterface $id): bool
    {
        return $this->repository->deleteNotificationSettings($id);
    }

    /**
     * Get notification settings by user ID
     */
    public function getByUserId(UuidInterface $userId): Collection
    {
        return $this->repository->getNotificationSettingsByUserId($userId);
    }

    /**
     * Get notification settings by type
     */
    public function getByType(string $type): Collection
    {
        return $this->repository->getNotificationSettingsByType($type);
    }

    /**
     * Get notification settings by reminder type
     */
    public function getByReminderType(string $reminderType): Collection
    {
        return $this->repository->getNotificationSettingsByReminderType($reminderType);
    }

    /**
     * Get active notification settings
     */
    public function getActiveSettings(): Collection
    {
        return $this->repository->getActiveNotificationSettings();
    }

    /**
     * Get daily reminder settings
     */
    public function getDailyReminderSettings(): Collection
    {
        return $this->repository->getDailyReminderSettings();
    }

    /**
     * Get weekly reminder settings
     */
    public function getWeeklyReminderSettings(): Collection
    {
        return $this->repository->getWeeklyReminderSettings();
    }

    /**
     * Get email notification settings
     */
    public function getEmailNotificationSettings(): Collection
    {
        return $this->repository->getEmailNotificationSettings();
    }

    /**
     * Get SMS notification settings
     */
    public function getSmsNotificationSettings(): Collection
    {
        return $this->repository->getSmsNotificationSettings();
    }

    /**
     * Toggle notification setting status
     */
    public function toggleStatus(UuidInterface $id): bool
    {
        $notificationSetting = $this->get($id);
        return $this->repository->updateNotificationSettings(
            ['is_active' => !$notificationSetting->is_active]
        );
    }
}

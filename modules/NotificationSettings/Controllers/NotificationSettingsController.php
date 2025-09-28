<?php

declare(strict_types=1);

namespace Modules\NotificationSettings\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\NotificationSettings\Handlers\DeleteNotificationSettingsHandler;
use Modules\NotificationSettings\Handlers\UpdateNotificationSettingsHandler;
use Modules\NotificationSettings\Presenters\NotificationSettingsPresenter;
use Modules\NotificationSettings\Requests\CreateNotificationSettingsRequest;
use Modules\NotificationSettings\Requests\DeleteNotificationSettingsRequest;
use Modules\NotificationSettings\Requests\GetNotificationSettingsListRequest;
use Modules\NotificationSettings\Requests\GetNotificationSettingsRequest;
use Modules\NotificationSettings\Requests\UpdateNotificationSettingsRequest;
use Modules\NotificationSettings\Services\NotificationSettingsCRUDService;
use Modules\NotificationSettings\Exports\NotificationSettingsExport;
use Modules\NotificationSettings\Requests\ExportNotificationSettingsRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class NotificationSettingsController extends Controller
{
    public function __construct(
        private NotificationSettingsCRUDService $notificationSettingsService,
        private UpdateNotificationSettingsHandler $updateNotificationSettingsHandler,
        private DeleteNotificationSettingsHandler $deleteNotificationSettingsHandler,
    ) {
    }

    public function index(GetNotificationSettingsListRequest $request): JsonResponse
    {
        $list = $this->notificationSettingsService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(NotificationSettingsPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetNotificationSettingsRequest $request): JsonResponse
    {
        $item = $this->notificationSettingsService->get();

        $presenter = new NotificationSettingsPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateNotificationSettingsRequest $request): JsonResponse
    {
        $createdItem = $this->notificationSettingsService->create($request->createCreateNotificationSettingsDTO());

        $presenter = new NotificationSettingsPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateNotificationSettingsRequest $request)
    {
        $command = $request->createUpdateNotificationSettingsCommand();
        $this->updateNotificationSettingsHandler->handle($command);

        $item = $this->notificationSettingsService->get();

        $presenter = new NotificationSettingsPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteNotificationSettingsRequest $request): JsonResponse
    {
        $this->deleteNotificationSettingsHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export notificationsettings to a file
     *
     * @param ExportNotificationSettingsRequest $request
     */
    public function export(ExportNotificationSettingsRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'notification_settings.' . $format;
        $filters = $request->getFilters();

        return Excel::download(new NotificationSettingsExport($this->notificationSettingsService, $filters), $fileName);
    }

    /**
     * Toggle notification setting status
     */
    public function toggleStatus(string $id): JsonResponse
    {
        $this->notificationSettingsService->toggleStatus(Uuid::fromString($id));

        $item = $this->notificationSettingsService->get(Uuid::fromString($id));
        $presenter = new NotificationSettingsPresenter($item);

        return Json::item($presenter->getData());
    }

    /**
     * Get notification settings by type
     */
    public function getByType(string $type): JsonResponse
    {
        $items = $this->notificationSettingsService->getByType($type);

        return Json::items(NotificationSettingsPresenter::collection($items));
    }

    /**
     * Get notification settings by reminder type
     */
    public function getByReminderType(string $reminderType): JsonResponse
    {
        $items = $this->notificationSettingsService->getByReminderType($reminderType);

        return Json::items(NotificationSettingsPresenter::collection($items));
    }

    /**
     * Get active notification settings
     */
    public function getActiveSettings(): JsonResponse
    {
        $items = $this->notificationSettingsService->getActiveSettings();

        return Json::items(NotificationSettingsPresenter::collection($items));
    }

    /**
     * Get daily reminder settings
     */
    public function getDailyReminders(): JsonResponse
    {
        $items = $this->notificationSettingsService->getDailyReminderSettings();

        return Json::items(NotificationSettingsPresenter::collection($items));
    }

    /**
     * Get weekly reminder settings
     */
    public function getWeeklyReminders(): JsonResponse
    {
        $items = $this->notificationSettingsService->getWeeklyReminderSettings();

        return Json::items(NotificationSettingsPresenter::collection($items));
    }
}

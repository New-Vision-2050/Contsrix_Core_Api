<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactMessage\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Modules\WebsiteCMS\WebsiteContactMessage\DTO\CreateWebsiteContactMessageDTO;
use Modules\WebsiteCMS\WebsiteContactMessage\DTO\ReplyToContactMessageDTO;
use Modules\WebsiteCMS\WebsiteContactMessage\Models\WebsiteContactMessage;
use Modules\WebsiteCMS\WebsiteContactMessage\Repositories\WebsiteContactMessageRepository;
use Modules\WebsiteCMS\WebsiteContactMessage\Notifications\ContactMessageReplyNotification;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class WebsiteContactMessageCRUDService
{
    use HasExportService;

    public function __construct(
        private WebsiteContactMessageRepository $repository,
    ) {
    }

    public function create(CreateWebsiteContactMessageDTO $createWebsiteContactMessageDTO): WebsiteContactMessage
    {
         return $this->repository->createWebsiteContactMessage($createWebsiteContactMessageDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): WebsiteContactMessage
    {
        return $this->repository->getWebsiteContactMessage(
            id: $id,
        );
    }

    public function replyToContactMessage(ReplyToContactMessageDTO $dto): WebsiteContactMessage
    {
        // Get the contact message
        $contactMessage = $this->repository->getWebsiteContactMessage($dto->id);

        // Update the status
        $this->repository->updateWebsiteContactMessage($dto->id, $dto->toArray());

        // Send email notification
        Notification::route('mail', $contactMessage->email)
            ->notify(new ContactMessageReplyNotification($contactMessage, $dto->replyMessage));

        // Return the updated contact message
        return $this->repository->getWebsiteContactMessage($dto->id);
    }
}

<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactMessage\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\WebsiteCMS\WebsiteContactMessage\Handlers\DeleteWebsiteContactMessageHandler;
use Modules\WebsiteCMS\WebsiteContactMessage\Handlers\UpdateWebsiteContactMessageHandler;
use Modules\WebsiteCMS\WebsiteContactMessage\Presenters\WebsiteContactMessagePresenter;
use Modules\WebsiteCMS\WebsiteContactMessage\Requests\CreateWebsiteContactMessageRequest;
use Modules\WebsiteCMS\WebsiteContactMessage\Requests\DeleteWebsiteContactMessageRequest;
use Modules\WebsiteCMS\WebsiteContactMessage\Requests\GetWebsiteContactMessageListRequest;
use Modules\WebsiteCMS\WebsiteContactMessage\Requests\GetWebsiteContactMessageRequest;
use Modules\WebsiteCMS\WebsiteContactMessage\Requests\UpdateWebsiteContactMessageRequest;
use Modules\WebsiteCMS\WebsiteContactMessage\Requests\ReplyToContactMessageRequest;
use Modules\WebsiteCMS\WebsiteContactMessage\Services\WebsiteContactMessageCRUDService;
use Modules\WebsiteCMS\WebsiteContactMessage\Exports\WebsiteContactMessageExport;
use Modules\WebsiteCMS\WebsiteContactMessage\Requests\ExportWebsiteContactMessageRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class WebsiteContactMessageController extends Controller
{
    public function __construct(
        private WebsiteContactMessageCRUDService $websiteContactMessageService,
        private UpdateWebsiteContactMessageHandler $updateWebsiteContactMessageHandler,
        private DeleteWebsiteContactMessageHandler $deleteWebsiteContactMessageHandler,
    ) {
    }

    public function index(GetWebsiteContactMessageListRequest $request): JsonResponse
    {
        $list = $this->websiteContactMessageService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(WebsiteContactMessagePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetWebsiteContactMessageRequest $request): JsonResponse
    {
        $item = $this->websiteContactMessageService->get(Uuid::fromString($request->route('id')));

        $presenter = new WebsiteContactMessagePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateWebsiteContactMessageRequest $request): JsonResponse
    {
        $createdItem = $this->websiteContactMessageService->create($request->createCreateWebsiteContactMessageDTO());

        $presenter = new WebsiteContactMessagePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateWebsiteContactMessageRequest $request): JsonResponse
    {
        $command = $request->createUpdateWebsiteContactMessageCommand();
        $this->updateWebsiteContactMessageHandler->handle($command);

        $item = $this->websiteContactMessageService->get($command->getId());

        $presenter = new WebsiteContactMessagePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteWebsiteContactMessageRequest $request): JsonResponse
    {
        $this->deleteWebsiteContactMessageHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export websitecontactmessage to a file
     *
     * @param ExportWebsiteContactMessageRequest $request
     */
    public function export(ExportWebsiteContactMessageRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'website_contact_message.' . $format;
        $filters = $request->getFilters();

        return Excel::download(new WebsiteContactMessageExport($this->websiteContactMessageService, $filters), $fileName);
    }

    /**
     * Reply to a contact message
     *
     * @param ReplyToContactMessageRequest $request
     * @return JsonResponse
     */
    public function reply(ReplyToContactMessageRequest $request): JsonResponse
    {
        $dto = $request->createReplyToContactMessageDTO();

        $updatedMessage = $this->websiteContactMessageService->replyToContactMessage($dto);

        $presenter = new WebsiteContactMessagePresenter($updatedMessage);

        return Json::success('Reply sent successfully and status updated');
    }
}

<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\SocialMediaLink\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\WebsiteCMS\SocialMediaLink\Handlers\DeleteSocialMediaLinkHandler;
use Modules\WebsiteCMS\SocialMediaLink\Handlers\UpdateSocialMediaLinkHandler;
use Modules\WebsiteCMS\SocialMediaLink\Presenters\SocialMediaLinkPresenter;
use Modules\WebsiteCMS\SocialMediaLink\Requests\CreateSocialMediaLinkRequest;
use Modules\WebsiteCMS\SocialMediaLink\Requests\DeleteSocialMediaLinkRequest;
use Modules\WebsiteCMS\SocialMediaLink\Requests\GetSocialMediaLinkListRequest;
use Modules\WebsiteCMS\SocialMediaLink\Requests\GetSocialMediaLinkRequest;
use Modules\WebsiteCMS\SocialMediaLink\Requests\UpdateSocialMediaLinkRequest;
use Modules\WebsiteCMS\SocialMediaLink\Requests\UpdateStatusRequest;
use Modules\WebsiteCMS\SocialMediaLink\Services\SocialMediaLinkCRUDService;
use Modules\WebsiteCMS\SocialMediaLink\Exports\SocialMediaLinkExport;
use Modules\WebsiteCMS\SocialMediaLink\Requests\ExportSocialMediaLinkRequest;
use Modules\WebsiteCMS\SocialMediaLink\Enums\SocialMediaType;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class SocialMediaLinkController extends Controller
{
    public function __construct(
        private SocialMediaLinkCRUDService $socialMediaLinkService,
        private UpdateSocialMediaLinkHandler $updateSocialMediaLinkHandler,
        private DeleteSocialMediaLinkHandler $deleteSocialMediaLinkHandler,
    ) {
    }

    public function index(GetSocialMediaLinkListRequest $request): JsonResponse
    {
        $list = $this->socialMediaLinkService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(SocialMediaLinkPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetSocialMediaLinkRequest $request): JsonResponse
    {
        $item = $this->socialMediaLinkService->get(Uuid::fromString($request->route('id')));

        $presenter = new SocialMediaLinkPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateSocialMediaLinkRequest $request): JsonResponse
    {
        $createdItem = $this->socialMediaLinkService->create($request->createCreateSocialMediaLinkDTO());

        $presenter = new SocialMediaLinkPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateSocialMediaLinkRequest $request): JsonResponse
    {
        $command = $request->createUpdateSocialMediaLinkCommand();
        $this->updateSocialMediaLinkHandler->handle($command);

        $item = $this->socialMediaLinkService->get($command->getId());

        $presenter = new SocialMediaLinkPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteSocialMediaLinkRequest $request): JsonResponse
    {
        $this->deleteSocialMediaLinkHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    public function updateStatus(UpdateStatusRequest $request): JsonResponse
    {
        $id = Uuid::fromString($request->route('id'));
        $status = (int) $request->get('status');

        $this->socialMediaLinkService->updateStatus($id, $status);

        $item = $this->socialMediaLinkService->get($id);
        $presenter = new SocialMediaLinkPresenter($item);

        return Json::item($presenter->getData());
    }

    public function getTypes(): JsonResponse
    {
        $types = array_map(function ($case) {
            return [
                'id' => $case->value,
                'value' => $case->label(),
            ];
        }, SocialMediaType::cases());

        return Json::items($types);
    }

    /**
     * Export socialmedialink to a file
     *
     * @param ExportSocialMediaLinkRequest $request
     */
    public function export(ExportSocialMediaLinkRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'social_media_link.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new SocialMediaLinkExport($this->socialMediaLinkService, $filters), $fileName);
    }
}

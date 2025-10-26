<?php

declare(strict_types=1);

namespace Modules\Shared\SocialIcon\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared\SocialIcon\Handlers\DeleteSocialIconHandler;
use Modules\Shared\SocialIcon\Handlers\UpdateSocialIconHandler;
use Modules\Shared\SocialIcon\Presenters\SocialIconPresenter;
use Modules\Shared\SocialIcon\Requests\CreateSocialIconRequest;
use Modules\Shared\SocialIcon\Requests\DeleteSocialIconRequest;
use Modules\Shared\SocialIcon\Requests\GetSocialIconListRequest;
use Modules\Shared\SocialIcon\Requests\GetSocialIconRequest;
use Modules\Shared\SocialIcon\Requests\UpdateSocialIconRequest;
use Modules\Shared\SocialIcon\Services\SocialIconCRUDService;
use Modules\Shared\SocialIcon\Exports\SocialIconExport;
use Modules\Shared\SocialIcon\Requests\ExportSocialIconRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class SocialIconController extends Controller
{
    public function __construct(
        private SocialIconCRUDService $socialIconService,
        private UpdateSocialIconHandler $updateSocialIconHandler,
        private DeleteSocialIconHandler $deleteSocialIconHandler,
    ) {
    }

    public function index(GetSocialIconListRequest $request): JsonResponse
    {
        $list = $this->socialIconService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(SocialIconPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetSocialIconRequest $request): JsonResponse
    {
        $item = $this->socialIconService->get(Uuid::fromString($request->route('id')));

        $presenter = new SocialIconPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateSocialIconRequest $request): JsonResponse
    {
        $createdItem = $this->socialIconService->create($request->createCreateSocialIconDTO());

        $presenter = new SocialIconPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateSocialIconRequest $request): JsonResponse
    {
        $command = $request->createUpdateSocialIconCommand();
        $this->updateSocialIconHandler->handle($command);

        $item = $this->socialIconService->get($command->getId());

        $presenter = new SocialIconPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteSocialIconRequest $request): JsonResponse
    {
        $this->deleteSocialIconHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export socialicon to a file
     *
     * @param ExportSocialIconRequest $request
     */
    public function export(ExportSocialIconRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'social_icon.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new SocialIconExport($this->socialIconService, $filters), $fileName);
    }
}

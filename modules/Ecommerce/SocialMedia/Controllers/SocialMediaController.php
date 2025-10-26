<?php

declare(strict_types=1);

namespace Modules\Ecommerce\SocialMedia\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\SocialMedia\Handlers\DeleteSocialMediaHandler;
use Modules\Ecommerce\SocialMedia\Handlers\UpdateSocialMediaHandler;
use Modules\Ecommerce\SocialMedia\Presenters\SocialMediaPresenter;
use Modules\Ecommerce\SocialMedia\Requests\CreateSocialMediaRequest;
use Modules\Ecommerce\SocialMedia\Requests\DeleteSocialMediaRequest;
use Modules\Ecommerce\SocialMedia\Requests\GetSocialMediaListRequest;
use Modules\Ecommerce\SocialMedia\Requests\GetSocialMediaRequest;
use Modules\Ecommerce\SocialMedia\Requests\UpdateSocialMediaRequest;
use Modules\Ecommerce\SocialMedia\Services\SocialMediaCRUDService;
use Modules\Ecommerce\SocialMedia\Exports\SocialMediaExport;
use Modules\Ecommerce\SocialMedia\Requests\ExportSocialMediaRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class SocialMediaController extends Controller
{
    public function __construct(
        private SocialMediaCRUDService $socialMediaService,
        private UpdateSocialMediaHandler $updateSocialMediaHandler,
        private DeleteSocialMediaHandler $deleteSocialMediaHandler,
    ) {
    }

    public function index(GetSocialMediaListRequest $request): JsonResponse
    {
        $list = $this->socialMediaService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(SocialMediaPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetSocialMediaRequest $request): JsonResponse
    {
        $item = $this->socialMediaService->get(Uuid::fromString($request->route('id')));

        $presenter = new SocialMediaPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateSocialMediaRequest $request): JsonResponse
    {
        $createdItem = $this->socialMediaService->create($request->createCreateSocialMediaDTO());

        $presenter = new SocialMediaPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateSocialMediaRequest $request): JsonResponse
    {
        $command = $request->createUpdateSocialMediaCommand();
        $this->updateSocialMediaHandler->handle($command);

        $item = $this->socialMediaService->get($command->getId());

        $presenter = new SocialMediaPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteSocialMediaRequest $request): JsonResponse
    {
        $this->deleteSocialMediaHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export socialmedia to a file
     *
     * @param ExportSocialMediaRequest $request
     */
    public function export(ExportSocialMediaRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'social_media.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new SocialMediaExport($this->socialMediaService, $filters), $fileName);
    }

    public function toggleStatus(GetSocialMediaRequest $request): JsonResponse
    {
        $socialMediaId = Uuid::fromString($request->route('id'));
        $updatedSocialMedia = $this->socialMediaService->toggleStatus($socialMediaId);
        
        $presenter = new SocialMediaPresenter($updatedSocialMedia);
        
        $message = $updatedSocialMedia->is_active ? 'تم تفعيل وسائل التواصل بنجاح' : 'تم إلغاء تفعيل وسائل التواصل بنجاح';
        
        return Json::item($presenter->getData(), message: $message);
    }
}

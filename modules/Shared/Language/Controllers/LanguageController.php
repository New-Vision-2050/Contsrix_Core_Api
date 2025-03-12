<?php

declare(strict_types=1);

namespace Modules\Shared\Language\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared\Language\Handlers\DeleteLanguageHandler;
use Modules\Shared\Language\Handlers\UpdateLanguageHandler;
use Modules\Shared\Language\Presenters\LanguagePresenter;
use Modules\Shared\Language\Requests\CreateLanguageRequest;
use Modules\Shared\Language\Requests\DeleteLanguageRequest;
use Modules\Shared\Language\Requests\GetLanguageListRequest;
use Modules\Shared\Language\Requests\GetLanguageRequest;
use Modules\Shared\Language\Requests\UpdateLanguageRequest;
use Modules\Shared\Language\Services\LanguageCRUDService;
use Ramsey\Uuid\Uuid;

class LanguageController extends Controller
{
    public function __construct(
        private LanguageCRUDService $languageService,
        private UpdateLanguageHandler $updateLanguageHandler,
        private DeleteLanguageHandler $deleteLanguageHandler,
    ) {
    }

    public function index(GetLanguageListRequest $request): JsonResponse
    {
        $list = $this->languageService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(LanguagePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetLanguageRequest $request): JsonResponse
    {
        $item = $this->languageService->get(Uuid::fromString($request->route('id')));

        $presenter = new LanguagePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateLanguageRequest $request): JsonResponse
    {
        $createdItem = $this->languageService->create($request->createCreateLanguageDTO());

        $presenter = new LanguagePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateLanguageRequest $request): JsonResponse
    {
        $command = $request->createUpdateLanguageCommand();
        $this->updateLanguageHandler->handle($command);

        $item = $this->languageService->get($command->getId());

        $presenter = new LanguagePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteLanguageRequest $request): JsonResponse
    {
        $this->deleteLanguageHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}

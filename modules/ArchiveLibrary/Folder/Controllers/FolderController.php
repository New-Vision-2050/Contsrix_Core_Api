<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\ArchiveLibrary\Folder\Handlers\DeleteFolderHandler;
use Modules\ArchiveLibrary\Folder\Handlers\UpdateFolderHandler;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\ArchiveLibrary\Folder\Presenters\FolderPresenter;
use Modules\ArchiveLibrary\Folder\Requests\CreateFolderRequest;
use Modules\ArchiveLibrary\Folder\Requests\DeleteFolderRequest;
use Modules\ArchiveLibrary\Folder\Requests\GetFolderListRequest;
use Modules\ArchiveLibrary\Folder\Requests\GetFolderRequest;
use Modules\ArchiveLibrary\Folder\Requests\UpdateFolderRequest;
use Modules\ArchiveLibrary\Folder\Requests\UploadFileRequest;
use Modules\ArchiveLibrary\Folder\Services\FileService;
use Modules\ArchiveLibrary\Folder\Services\FolderCRUDService;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\Uuid;

class FolderController extends Controller
{
    public function __construct(
        private FolderCRUDService $folderService,
        private UpdateFolderHandler $updateFolderHandler,
        private DeleteFolderHandler $deleteFolderHandler,
        private FileService $fileService
    ) {
    }

    public function index(GetFolderListRequest $request)//: JsonResponse
    {
        $list = $this->folderService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );


        return Json::items(FolderPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetFolderRequest $request): JsonResponse
    {
        $item = $this->folderService->get(Uuid::fromString($request->route('id')));

        $presenter = new FolderPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateFolderRequest $request): JsonResponse
    {
        $createdItem = $this->folderService->create($request->createCreateFolderDTO());

        $presenter = new FolderPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateFolderRequest $request): JsonResponse
    {
        $command = $request->createUpdateFolderCommand();
        $this->updateFolderHandler->handle($command);

        $item = $this->folderService->get($command->getId());

        $presenter = new FolderPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteFolderRequest $request): JsonResponse
    {
        $this->deleteFolderHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
    public function getChildFolders(string $id, GetFolderListRequest $request): JsonResponse
    {
        $parentId = Uuid::fromString($id);

        $list = $this->folderService->listByParent(
            $parentId,
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(FolderPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }
    public function file(UploadFileRequest $request)
    {
        $fileUploded = $this->fileService->getFolderPath($request);

        return Json::item($fileUploded);

    }




}

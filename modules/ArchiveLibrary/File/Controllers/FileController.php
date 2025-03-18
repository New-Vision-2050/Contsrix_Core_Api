<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\ArchiveLibrary\File\Handlers\DeleteFileHandler;
use Modules\ArchiveLibrary\File\Handlers\UpdateFileHandler;
use Modules\ArchiveLibrary\File\Presenters\FilePresenter;
use Modules\ArchiveLibrary\File\Requests\CreateFileRequest;
use Modules\ArchiveLibrary\File\Requests\DeleteFileRequest;
use Modules\ArchiveLibrary\File\Requests\GetFileListRequest;
use Modules\ArchiveLibrary\File\Requests\GetFileRequest;
use Modules\ArchiveLibrary\File\Requests\UpdateFileRequest;
use Modules\ArchiveLibrary\File\Services\FileCRUDService;
use Ramsey\Uuid\Uuid;

class FileController extends Controller
{
    public function __construct(
        private FileCRUDService $fileService,
        private UpdateFileHandler $updateFileHandler,
        private DeleteFileHandler $deleteFileHandler,
    ) {
    }

    public function index(GetFileListRequest $request): JsonResponse
    {
        $list = $this->fileService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(FilePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetFileRequest $request): JsonResponse
    {
        $item = $this->fileService->get(Uuid::fromString($request->route('id')));

        $presenter = new FilePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateFileRequest $request): JsonResponse
    {
        $createdItem = $this->fileService->create($request->createCreateFileDTO());

        $presenter = new FilePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateFileRequest $request): JsonResponse
    {
        $command = $request->createUpdateFileCommand();
        $this->updateFileHandler->handle($command);

        $item = $this->fileService->get($command->getId());

        $presenter = new FilePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteFileRequest $request): JsonResponse
    {
        $this->deleteFileHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}

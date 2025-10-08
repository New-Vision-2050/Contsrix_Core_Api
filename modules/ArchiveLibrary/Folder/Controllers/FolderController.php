<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
use Modules\ArchiveLibrary\File\Presenters\FilePresenter;
use Modules\Audit\Presenters\AuditPresenter;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\User\Presenters\UserPresenter;
use Ramsey\Uuid\Uuid;

class FolderController extends Controller
{
    public function __construct(
        private FolderCRUDService   $folderService,
        private UpdateFolderHandler $updateFolderHandler,
        private DeleteFolderHandler $deleteFolderHandler,
        private FileService         $fileService
    )
    {
    }

    public function index(GetFolderListRequest $request)//: JsonResponse
    {
        $list = $this->folderService->list(
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10)
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

        return Json::item($presenter->getData());
    }

    public function delete(DeleteFolderRequest $request): JsonResponse
    {
        $this->deleteFolderHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    public function getChildFolders(string $id, GetFolderListRequest $request)
    {
        $parentId = Uuid::fromString($id);

        $list = $this->folderService->listByParent(
            $parentId,
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10)
        );
        return Json::items(FolderPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function file(UploadFileRequest $request)
    {
        $fileUploded = $this->fileService->getFolderPath($request);

        return Json::item($fileUploded);

    }


    public function showFolders(GetFolderListRequest $request)//: JsonResponse
    {
        $userId = auth()->user()->id;
        $parentId = $request->get('parent_id');

        $list = $this->folderService->listFolders(
            $userId,
            $parentId,
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10)
        );

        // Return the response in JSON format with pagination info
        return Json::items($list['data'], paginationSettings: $list['pagination']);
    }

    public function getUsersAllowedByFolderId(Request $request)
    {
        $folderId = $request->route("id");

        $users = $this->folderService->getUsersAllowedByFolderId($folderId);

        return Json::items(UserPresenter::collection($users));
    }

    public function getFoldersAndFiles(GetFolderListRequest $request): JsonResponse
    {
        $userId = auth()->user()->id;
        $parentId = $request->get('parent_id');
        $page = (int)$request->get('page', 1);
        $perPage = (int)$request->get('per_page', 10);

        $result = $this->folderService->getFoldersAndFiles($userId, $parentId, $page, $perPage);

        return Json::item([
            'folders' => FolderPresenter::collection($result['folders']),
            'files' => FilePresenter::collection($result['files'])],["pagination"=>$result['pagination']]);
    }

    /**
     * Get audit logs for a folder and all its related files
     */
    public function getFolderAudits(Request $request): JsonResponse
    {
        $folderId = Uuid::fromString($request->route('id'));

        $audits = $this->folderService->getFolderAudits($folderId);

        return Json::items(
//            'folder_audits' => AuditPresenter::collection($audits['folder_audits']),
//            'file_audits' => AuditPresenter::collection($audits['file_audits']),
            AuditPresenter::collection($audits)
        );
    }

}

<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Notification;
use Modules\ArchiveLibrary\File\Handlers\DeleteFileHandler;
use Modules\ArchiveLibrary\File\Handlers\UpdateFileHandler;
use Modules\ArchiveLibrary\File\Notifications\FileSharedNotification;
use Modules\ArchiveLibrary\File\Presenters\FilePresenter;
use Modules\ArchiveLibrary\File\Requests\ChangeArchiveStatusRequest;
use Modules\ArchiveLibrary\File\Requests\CopyFileRequest;
use Modules\ArchiveLibrary\File\Requests\CreateFileRequest;
use Modules\ArchiveLibrary\File\Requests\CutFileRequest;
use Modules\ArchiveLibrary\File\Requests\DeleteFileRequest;
use Modules\ArchiveLibrary\File\Requests\GetFileListRequest;
use Modules\ArchiveLibrary\File\Requests\GetFileRequest;
use Modules\ArchiveLibrary\File\Requests\GetFilesWithWidgetsRequest;
use Modules\ArchiveLibrary\File\Requests\ShareFileRequest;
use Modules\ArchiveLibrary\File\Requests\UpdateFileRequest;
use Modules\ArchiveLibrary\File\Services\FileCRUDService;
use Modules\ArchiveLibrary\Folder\Presenters\FolderPresenter;
use Modules\ArchiveLibrary\Folder\Services\FolderCRUDService;
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;

class FileController extends Controller
{
    public function __construct(
        private FileCRUDService   $fileService,
        private FolderCRUDService $folderService,
        private UpdateFileHandler $updateFileHandler,
        private DeleteFileHandler $deleteFileHandler,
    )
    {
    }

    public function index(GetFileListRequest $request): JsonResponse
    {
        $list = $this->fileService->list(
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10)
        );

        return Json::items(FilePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetFileRequest $request): JsonResponse
    {
        $item = $this->fileService->get(Uuid::fromString($request->route('id')));


        $presenter = new FilePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateFileRequest $request)
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

        return Json::item($presenter->getData());
    }

    public function delete(DeleteFileRequest $request): JsonResponse
    {
        $this->deleteFileHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    public function changeStatus(ChangeArchiveStatusRequest $request): JsonResponse
    {
        $id = Uuid::fromString($request->route('id'));
        $type = $request->getType();
        $status = $request->getStatus();

        if ($type === 'file') {
            $item = $this->fileService->get($id);
            $item->update(['status' => $status]);
            $presenter = new FilePresenter($item->fresh());
        } else {
            $item = $this->folderService->get($id);
            $item->update(['status' => $status]);
            $presenter = new FolderPresenter($item->fresh());
        }

        return Json::item($presenter->getData());
    }

    public function getFilesWithWidgets(GetFilesWithWidgetsRequest $request): JsonResponse
    {
        $result = $this->fileService->getFilesWithWidgets(
            $request->getFolderId()

        );

        return response()->json([
            'status' => true,
            'message' => 'Files retrieved successfully',
            'data' => [

                'total_files_count' => $result['widgets']['total_files_count'],
                'expired_files_count' => $result['widgets']['expired_files_count'],
                'expired_files_percentage' => round($result['widgets']['expired_files_percentage'],2),
                'valid_files_count' => $result['widgets']['valid_files_count'],
                'valid_files_percentage' => round($result['widgets']['valid_files_percentage'],2),
                'almost_expired_files_count' => $result['widgets']['almost_expired_files_count'],
                'almost_expired_files_percentage' => round($result['widgets']['almost_expired_files_percentage'],2),
                'almost_expired_files' => FilePresenter::collection($result['widgets']['almost_expired_files']),
            ]
        ]);
    }

    public function copyFile(CopyFileRequest $request): JsonResponse
    {
        $copiedFile = $this->fileService->copyFile(
            $request->getFileId(),
            $request->getFolderId()
        );

        $presenter = new FilePresenter($copiedFile);

        return Json::item($presenter->getData());
    }

    public function cutFile(CutFileRequest $request): JsonResponse
    {
        $movedFile = $this->fileService->cutFile(
            $request->getFileId(),
            $request->getFolderId()
        );

        $presenter = new FilePresenter($movedFile);

        return Json::item($presenter->getData());
    }

    public function shareFile(ShareFileRequest $request): JsonResponse
    {
        $result = $this->fileService->shareFile(
            $request->getFileId(),
            $request->getUserIds()
        );

        $notificationsSent = 0;
        
        // Only notify new users (not existing ones)
        if (!empty($result['new_user_ids'])) {
            // Get only the newly added users to notify
            $newUsers = User::whereIn('id', $result['new_user_ids'])->get();
            
            // Get the authenticated user who is sharing
            $sharedBy = auth()->user();
            $sharedByName = $sharedBy->name ?? 'A user';

            // Send email notifications only to new users
            Notification::send(
                $newUsers, 
                new FileSharedNotification(
                    $result['share_url'], 
                    $result['file'],
                    $sharedByName
                )
            );
            
            $notificationsSent = $newUsers->count();
        }

        return response()->json([
            'status' => true,
            'message' => $notificationsSent > 0 
                ? 'File shared successfully and notifications sent to new users' 
                : 'File shared successfully (no new users to notify)',
            'data' => [
                'file' => (new FilePresenter($result['file']))->getData(),
                'share_url' => $result['share_url'],
                'shared_with_count' => $result['shared_with_count'],
                'new_users_count' => count($result['new_user_ids']),
                'existing_users_count' => count($result['existing_user_ids']),
                'notifications_sent' => $notificationsSent,
            ]
        ]);
    }
}

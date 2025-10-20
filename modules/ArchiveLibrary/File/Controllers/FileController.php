<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Modules\ArchiveLibrary\File\Handlers\DeleteFileHandler;
use Modules\ArchiveLibrary\File\Handlers\UpdateFileHandler;
use Modules\ArchiveLibrary\File\Notifications\FileSharedNotification;
use Modules\ArchiveLibrary\File\Presenters\FilePresenter;
use Modules\ArchiveLibrary\File\Presenters\FavouriteFilePresenter;
use Modules\ArchiveLibrary\File\Requests\ChangeArchiveStatusRequest;
use Modules\ArchiveLibrary\File\Requests\CopyFileRequest;
use Modules\ArchiveLibrary\File\Requests\CreateFileRequest;
use Modules\ArchiveLibrary\File\Requests\CutFileRequest;
use Modules\ArchiveLibrary\File\Requests\DeleteFileRequest;
use Modules\ArchiveLibrary\File\Requests\DownloadFileMediaRequest;
use Modules\ArchiveLibrary\File\Requests\DownloadSingleFileRequest;
use Modules\ArchiveLibrary\File\Requests\ExportFileRequest;
use Modules\ArchiveLibrary\File\Requests\GetFileListRequest;
use Modules\ArchiveLibrary\File\Requests\GetFileRequest;
use Modules\ArchiveLibrary\File\Requests\GetFilesWithWidgetsRequest;
use Modules\ArchiveLibrary\File\Requests\ManageFavouritesRequest;
use Modules\ArchiveLibrary\File\Requests\ShareFileRequest;
use Modules\ArchiveLibrary\File\Requests\UpdateFileRequest;
use Modules\ArchiveLibrary\File\Services\FileCRUDService;
use Modules\ArchiveLibrary\File\Services\FileFavouritesService;
use Modules\ArchiveLibrary\File\Exports\FileExport;
use Modules\ArchiveLibrary\Folder\Presenters\FolderPresenter;
use Modules\ArchiveLibrary\Folder\Services\FolderCRUDService;
use Modules\User\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class FileController extends Controller
{
    public function __construct(
        private FileCRUDService   $fileService,
        private FolderCRUDService $folderService,
        private UpdateFileHandler $updateFileHandler,
        private DeleteFileHandler $deleteFileHandler,
        private FileFavouritesService $favouritesService,
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
                "all_file_space"=>$result['widgets']["all_file_space"],
                "all_remain_file_space"=>$result['widgets']["all_remain_file_space"],
                "all_consumed_file_space"=>$result['widgets']["all_consumed_file_space"]

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
            $request->getFileIds(),
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

            // Send notifications for each file to new users
            foreach ($result['files'] as $index => $file) {
                $shareUrl = $result['share_urls'][$index] ?? '';
                
                Notification::send(
                    $newUsers,
                    new FileSharedNotification(
                        $shareUrl,
                        $file,
                        $sharedByName
                    )
                );
            }

            $notificationsSent = $newUsers->count() * count($result['files']);
        }

        return response()->json([
            'status' => true,
            'message' => $notificationsSent > 0
                ? 'Files shared successfully and notifications sent to new users'
                : 'Files shared successfully (no new users to notify)',
            'data' => [
                'files' => FilePresenter::collection($result['files']),
                'share_urls' => $result['share_urls'],
                'files_count' => $result['files_count'],
                'shared_with_count' => $result['shared_with_count'],
                'new_users_count' => count($result['new_user_ids']),
                'existing_users_count' => count($result['existing_user_ids']),
                'notifications_sent' => $notificationsSent,
            ]
        ]);
    }

    /**
     * Export files to a file
     *
     * @param ExportFileRequest $request
     */
    public function export(ExportFileRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'files.' . $format;

        $filters = $request->getFilters();

        return Excel::download(new FileExport($this->fileService, $filters), $fileName);
    }

    /**
     * Download single file media
     *
     */
    public function downloadSingleFile(DownloadSingleFileRequest $request)
    {
        $file = $this->fileService->get(Uuid::fromString($request->route('id')));
        $collection = $request->getCollection();
        $userId = auth()->id();

        // Check if file is private and validate user access
        if ($file->access_type === 'private') {
            $hasAccess = $file->users()
                ->where('user_id', $userId)
                ->exists();

            if (!$hasAccess) {
                return response()->json([
                    'status' => false,
                    'message' => 'Access denied. You do not have permission to download this file.',
                ], 403);
            }
        }

        // Try to get media
        $mediaItem = $file->getFirstMedia($collection);

        // If no media in the specified collection, try mediaFile relation
        if (!$mediaItem && $collection === 'upload') {
            $mediaItem = $file->mediaFile;
        }

        // If still no media found, return error
        if (!$mediaItem) {
            return response()->json([
                'status' => false,
                'message' => 'No media found for this file',
                'collection_checked' => $collection,
                'media_count' => $file->media->count(),
                'mediaFile_exists' => $file->mediaFile ? 'yes' : 'no',
            ], 404);
        }

        try {
            $disk = $mediaItem->disk;
            $mediaPath = $mediaItem->getPath();
            $fileName = $mediaItem->file_name;

            // Check if file is stored on S3 or locally
            if ($disk === 's3_public' || $disk === 's3' || str_starts_with($disk, 's3_')) {
                // File is on S3 - stream directly from S3
                $s3Path = str_replace('\\', '/', $mediaPath);
                
                if (!Storage::disk($disk)->exists($s3Path)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'File does not exist on S3',
                        's3_path' => $s3Path,
                        'disk' => $disk,
                    ], 404);
                }

                // Get file content from S3
                $fileContent = Storage::disk($disk)->get($s3Path);
                $mimeType = $mediaItem->mime_type;

                // Return file as download response
                return response($fileContent, 200, [
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                    'Content-Length' => strlen($fileContent),
                ]);
            } else {
                // File is stored locally
                if (!file_exists($mediaPath)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'File does not exist on local disk',
                        'local_path' => $mediaPath,
                    ], 404);
                }

                // Return local file as download
                return response()->download($mediaPath, $fileName, [
                    'Content-Type' => $mediaItem->mime_type,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to download file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download file media as ZIP (multiple files)
     *
     */
    public function downloadMedia(DownloadFileMediaRequest $request)
    {
        $fileIds = $request->getFileIds();
        $collection = $request->getCollection();
        $userId = auth()->id();

        $accessDeniedFiles = [];
        $missingMediaFiles = [];
        $successfulFiles = [];
        $debugInfo = [
            'file_ids_count' => count($fileIds),
            'collection' => $collection,
            'user_id' => $userId,
        ];

        // Validate access for each file
        foreach ($fileIds as $fileId) {
            $file = $this->fileService->get(Uuid::fromString($fileId));

            // Check if file is private and validate user access
            if ($file->access_type === 'private') {
                $hasAccess = $file->users()
                    ->where('user_id', $userId)
                    ->exists();

                if (!$hasAccess) {
                    $accessDeniedFiles[] = [
                        'id' => $file->id,
                        'name' => $file->name
                    ];
                    continue;
                }
            }

            // Try to get media
            $mediaItem = $file->getFirstMedia($collection);

            // If no media in the specified collection, try mediaFile relation
            if (!$mediaItem && $collection === 'upload') {
                $mediaItem = $file->mediaFile;
            }

            if (!$mediaItem) {
                $missingMediaFiles[] = [
                    'id' => $file->id,
                    'name' => $file->name,
                    'collection_checked' => $collection,
                    'media_count' => $file->media->count(),
                    'mediaFile_exists' => $file->mediaFile ? 'yes' : 'no',
                ];
                continue;
            }

            $successfulFiles[] = [
                'file' => $file,
                'media' => $mediaItem,
                'media_id' => $mediaItem->id,
                'media_file_name' => $mediaItem->file_name,
                'media_path' => $mediaItem->getPath(),
            ];
        }

        // If no files can be downloaded
        if (empty($successfulFiles)) {
            return response()->json([
                'status' => false,
                'message' => 'No files available for download',
                'debug' => $debugInfo,
                'access_denied' => $accessDeniedFiles,
                'missing_media' => $missingMediaFiles,
            ], 403);
        }

        // Create ZIP file with proper path separators
        $zipFileName = 'files_' . now()->format('Y-m-d_His') . '.zip';
        $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp');
        $zipPath = $tempDir . DIRECTORY_SEPARATOR . $zipFileName;

        // Ensure temp directory exists
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create ZIP file. Path: ' . $zipPath,
            ], 500);
        }

        // Add files to ZIP
        $fileCounter = [];
        $addedFilesCount = 0;
        $skippedFiles = [];
        $tempFiles = []; // Track temporary files for cleanup

        foreach ($successfulFiles as $item) {
            $media = $item['media'];
            $file = $item['file'];

            $originalFileName = $media->file_name;

            // Handle duplicate filenames by adding counter
            if (isset($fileCounter[$originalFileName])) {
                $fileCounter[$originalFileName]++;
                $pathInfo = pathinfo($originalFileName);
                $fileName = $pathInfo['filename'] . '_' . $fileCounter[$originalFileName] . '.' . ($pathInfo['extension'] ?? '');
            } else {
                $fileCounter[$originalFileName] = 1;
                $fileName = $originalFileName;
            }

            try {
                // Check if file is stored on S3 or locally
                $disk = $media->disk;
                $mediaPath = $media->getPath();

                if ($disk === 's3_public' || $disk === 's3' || str_starts_with($disk, 's3_')) {
                    // File is on S3 - download to temp location
                    $s3Path = str_replace('\\', '/', $mediaPath); // Normalize path for S3
                    
                    if (Storage::disk($disk)->exists($s3Path)) {
                        $tempFilePath = $tempDir . DIRECTORY_SEPARATOR . 'download_' . uniqid() . '_' . $fileName;
                        $fileContent = Storage::disk($disk)->get($s3Path);
                        file_put_contents($tempFilePath, $fileContent);
                        
                        $tempFiles[] = $tempFilePath; // Track for cleanup
                        
                        if ($zip->addFile($tempFilePath, $fileName)) {
                            $addedFilesCount++;
                        } else {
                            $skippedFiles[] = [
                                'file_id' => $file->id,
                                'file_name' => $file->name,
                                'media_file_name' => $fileName,
                                'reason' => 'Failed to add S3 file to ZIP'
                            ];
                        }
                    } else {
                        $skippedFiles[] = [
                            'file_id' => $file->id,
                            'file_name' => $file->name,
                            'media_file_name' => $fileName,
                            's3_path' => $s3Path,
                            'disk' => $disk,
                            'reason' => 'File does not exist on S3'
                        ];
                    }
                } else {
                    // File is stored locally
                    $filePath = $mediaPath;
                    
                    if (file_exists($filePath)) {
                        if ($zip->addFile($filePath, $fileName)) {
                            $addedFilesCount++;
                        } else {
                            $skippedFiles[] = [
                                'file_id' => $file->id,
                                'file_name' => $file->name,
                                'media_file_name' => $fileName,
                                'reason' => 'Failed to add local file to ZIP'
                            ];
                        }
                    } else {
                        $skippedFiles[] = [
                            'file_id' => $file->id,
                            'file_name' => $file->name,
                            'media_file_name' => $fileName,
                            'local_path' => $filePath,
                            'reason' => 'File does not exist locally'
                        ];
                    }
                }
            } catch (\Exception $e) {
                $skippedFiles[] = [
                    'file_id' => $file->id,
                    'file_name' => $file->name,
                    'media_file_name' => $fileName,
                    'reason' => 'Exception: ' . $e->getMessage()
                ];
            }
        }

        // Check if any files were added
        if ($addedFilesCount === 0) {
            $zip->close();
            @unlink($zipPath); // Clean up empty ZIP
            
            // Clean up temp files
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    @unlink($tempFile);
                }
            }
            
            return response()->json([
                'status' => false,
                'message' => 'No files could be added to ZIP',
                'debug' => $debugInfo,
                'successful_files_data' => $successfulFiles,
                'skipped_files' => $skippedFiles,
                'successful_files_count' => count($successfulFiles),
            ], 500);
        }

        $zip->close();

        // Verify ZIP file was created successfully
        if (!file_exists($zipPath)) {
            // Clean up temp files
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    @unlink($tempFile);
                }
            }
            
            return response()->json([
                'status' => false,
                'message' => 'Failed to create ZIP file. File does not exist at: ' . $zipPath,
                'debug' => $debugInfo,
                'added_files_count' => $addedFilesCount,
                'successful_files_count' => count($successfulFiles),
                'skipped_files' => $skippedFiles,
            ], 500);
        }

        // Return ZIP file for download and delete after sending
        $response = response()->download($zipPath, $zipFileName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);

        // Clean up temporary files downloaded from S3
        register_shutdown_function(function () use ($tempFiles) {
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    @unlink($tempFile);
                }
            }
        });

        return $response;
    }

    /**
     * Add files to user's favourites list
     * Validates access for private files before adding
     *
     * @param ManageFavouritesRequest $request
     * @return JsonResponse
     */
    public function addToFavourites(ManageFavouritesRequest $request): JsonResponse
    {
        $user = auth()->user();
        $fileIds = $request->getFileIds();

        $results = $this->favouritesService->addToFavourites($user, $fileIds);

        return response()->json([
            'status' => true,
            'message' => 'Favourites processed successfully',
            'data' => $results,
            'summary' => [
                'total_requested' => count($fileIds),
                'added_count' => count($results['added']),
                'already_favourite_count' => count($results['already_favourite']),
                'access_denied_count' => count($results['access_denied']),
                'not_found_count' => count($results['not_found'])
            ]
        ]);
    }

    /**
     * Remove files from user's favourites list
     *
     * @param ManageFavouritesRequest $request
     * @return JsonResponse
     */
    public function removeFromFavourites(ManageFavouritesRequest $request): JsonResponse
    {
        $user = auth()->user();
        $fileIds = $request->getFileIds();

        $removedCount = $this->favouritesService->removeFromFavourites($user, $fileIds);

        return response()->json([
            'status' => true,
            'message' => 'Files removed from favourites successfully',
            'data' => [
                'removed_count' => $removedCount,
                'requested_count' => count($fileIds)
            ]
        ]);
    }

    /**
     * Get user's favourite files list
     *
     * @return JsonResponse
     */
    public function getFavourites(): JsonResponse
    {
        $user = auth()->user();
        $favourites = $this->favouritesService->getFavourites($user);

        return Json::items(FavouriteFilePresenter::collection($favourites));
    }
}

<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Services;

use Modules\ArchiveLibrary\Folder\Repositories\FolderRepository;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class FileService
{
    public function __construct(
        private FolderRepository $repository,
        private FileUploadService  $fileUploadService,
    ) {
    }

    public function getFolderPath($request)
    {
        $file = $request->getFile();
        $folderId = Uuid::fromString($request->getFolderId());
        $visibility = $request->getVisibility();

        $folder = $this->repository->getFolder($folderId);

        $path = $folder->name;

        while ($folder->parent_id) {
            $parentUuid = Uuid::fromString($folder->parent_id);

            $folder = $this->repository->getFolder($parentUuid);

            if ($folder) {
                $path = $folder->name . '/' . $path;
            }
        }

        $media = $this->fileUploadService->uploadFile($folder, $file, $path, 'upload', $visibility, $folderId->toString() );
        return $media->getFullUrl();
    }
}

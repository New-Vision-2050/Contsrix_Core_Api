<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Services;

use Illuminate\Support\Collection;
use Modules\ArchiveLibrary\Folder\DTO\CreateFolderDTO;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\ArchiveLibrary\Folder\Repositories\FolderRepository;
use Ramsey\Uuid\UuidInterface;
use Ramsey\Uuid\Uuid;
class FolderCRUDService
{
    public function __construct(
        private FolderRepository $repository,
    ) {
    }

    public function create(CreateFolderDTO $createFolderDTO): Folder
    {
         return $this->repository->createFolder($createFolderDTO->toArray(),$createFolderDTO->getUserIds(),$createFolderDTO->getFile());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }



    public function get(UuidInterface $id): Folder
    {
        return $this->repository->getFolder(
            id: $id,
        );
    }

    public function showFolders(UuidInterface $userId, int $page = 1, int $perPage = 10)
    {
        $folders = $this->repository->getFolderList($page, $perPage);

        $foldersData = [];

        foreach ($folders as $folder) {
            if ($folder->access_type === 'public') {
                $files = $folder->files()->where('access_type', 'public')->get();
                $foldersData[] = [
                    'folder' => $folder,
                    'files' => $files,
                ];
            } else {
                if ($this->repository->canViewFolder($folder->id, $userId)) {
                    $files = $this->repository->getViewableFilesInFolder($folder->id, $userId);
                    $foldersData[] = [
                        'folder' => $folder,
                        'files' => $files,
                    ];
                }
            }
        }

        return [
            'data' => $foldersData,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
            ],
        ];
    }

    public function listByParent( $parentId, $userId, int $page = 1, int $perPage = 10): array
    {
        $folders = $this->repository->getChildFolders($parentId, $page, $perPage);

        $foldersData = [];

        foreach ($folders as $folder) {
            // if ($folder->access_type === 'public') {

                $files = $folder->files()
                // ->where('access_type', 'public')
                ->get();
                $foldersData[] = [
                    'folder' => $folder,
                    'files' => $files,
                ];
            // } else {
            //     if ($this->repository->canViewFolder($folder->id, $userId)) {
            //         $files = $this->repository->getViewableFilesInFolder($folder->id, $userId);
            //         $foldersData[] = [
            //             'folder' => $folder,
            //             'files' => $files,
            //         ];
            //     }
            // }
        }

        return [
            'data' => $foldersData,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $folders->count(),
            ],
        ];
    }

    public function listFolders(UuidInterface $userId, ?string $parentId, int $page = 1, int $perPage = 10)
    {

        if ($parentId) {
            $parentId = Uuid::fromString($parentId);

            return $this->listByParent($parentId, $page, $perPage);
        } else {
            // If no parentId, list top-level folders
            return $this->showFolders($userId, $page, $perPage);
        }
    }

    public function getFoldersAndFiles(
        $userId,
        ?string $parentId,
        int $page = 1,
        int $perPage = 10,
        ?string $documentType = null,
        ?bool $isFavourite = null,
        ?string $endDate = null,
        ?string $endDateFrom = null,
        ?string $endDateTo = null,
        ?string $search = null,
        string $searchType = 'all',
        ?int $branchId = null,
        ?string $sort = null,
        bool $withoutTenancy = false
    )
    {
        return $this->repository->getFoldersAndFilesByParent(
            $parentId,
            $userId,
            $page,
            $perPage,
            $documentType,
            $isFavourite,
            $endDate,
            $endDateFrom,
            $endDateTo,
            $search,
            $searchType,
            $branchId,
            $sort,
            $withoutTenancy
        );
    }

    public function getUsersAllowedByFolderId($folderId)
    {

        return $this->repository->getUsersAllowedByFolderId($folderId);
    }

    /**
     * Get audit logs based on type
     * @param UuidInterface $id - Folder or File ID
     * @param string $type - 'folder' or 'file'
     */
    public function getFolderAudits(UuidInterface $id, string $type = 'folder')
    {
        if ($type === 'file') {
            // Get audits for a specific file only
            return \Modules\Audit\Models\Audit::where('auditable_id', $id)
                ->where('auditable_type', \Modules\ArchiveLibrary\File\Models\File::class)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Type is 'folder' - get folder and all its files audits
        $folder = $this->repository->getFolder($id);

        // Get folder audits
        $folderAudits = \Modules\Audit\Models\Audit::where('auditable_id', $folder->id)
            ->where('auditable_type', \Modules\ArchiveLibrary\Folder\Models\Folder::class)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get all file IDs for this folder
        $fileIds = $folder->files()->pluck('id')->toArray();

        // Get audits for all files in this folder
        $fileAudits = collect();
        if (!empty($fileIds)) {
            $fileAudits = \Modules\Audit\Models\Audit::whereIn('auditable_id', $fileIds)
                ->where('auditable_type', \Modules\ArchiveLibrary\File\Models\File::class)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return $folderAudits->merge($fileAudits)->sortByDesc('created_at')->values();
    }

}

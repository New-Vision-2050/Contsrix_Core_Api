<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Services;

use App\Exceptions\CustomException;
use DB;
use Illuminate\Support\Collection;
use Modules\ArchiveLibrary\File\DTO\CreateFileDTO;
use Modules\ArchiveLibrary\File\Models\File;
use Modules\ArchiveLibrary\File\Repositories\FileRepository;
use Ramsey\Uuid\UuidInterface;
use ZipStream\Exception;

class FileCRUDService
{
    public function __construct(
        private FileRepository $repository,
    ) {
    }

    public function create(CreateFileDTO $createFileDTO): File
    {
        try {
            DB::beginTransaction();
            $file = $this->repository->createFile($createFileDTO->toArray() , $createFileDTO->getFile());

            if (!empty($createFileDTO->getUserIds())) {
                $this->repository->attachUsers($file, $createFileDTO->getUserIds());
            }
            DB::commit();
        }catch (Exception $e){
            DB::rollBack();
            throw new CustomException($e->getMessage());
        }


         return $file;
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): File
    {
        return $this->repository->getFile(
            id: $id,
        );
    }

    public function getFilesWithWidgets(?string $folderId): array
    {
        $filesData = $this->repository->getFilesByFolder($folderId);

        $expiredFilesCount= $this->repository->getExpiredFilesCount($folderId);
        $validFilesCount= $this->repository->getValidFilesCount($folderId);
        $almostExpiredFilesCount= $this->repository->getAlmostExpiredFilesCount($folderId);

        $widgets = [
            'total_files_count' => $this->repository->getTotalFilesCount(),
            'expired_files_count' => $expiredFilesCount,
            'expired_files_percentage' => $filesData['count'] !=0?($expiredFilesCount/$filesData['count'])*100:0,
            'valid_files_count' => $validFilesCount,
            'valid_files_percentage' => $filesData['count'] !=0?($validFilesCount/$filesData['count'])*100:0,
            'almost_expired_files_count' => $almostExpiredFilesCount,
            'almost_expired_files_percentage' => $filesData['count'] !=0?($almostExpiredFilesCount/$filesData['count'])*100:0,
            'almost_expired_files' => $this->repository->getAlmostExpiredFiles($folderId),
        ];

        return [
            'files' => $filesData['data'],
            'widgets' => $widgets,
        ];
    }

    public function copyFile(UuidInterface $fileId, ?UuidInterface $targetFolderId): File
    {
        return $this->repository->copyFile($fileId, $targetFolderId);
    }

    public function cutFile(UuidInterface $fileId, ?UuidInterface $targetFolderId): File
    {
        return $this->repository->cutFile($fileId, $targetFolderId);
    }
}

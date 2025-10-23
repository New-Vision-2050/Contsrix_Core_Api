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

        $limitSize = $this->repository->getLimitSize();
        $allFileSpace= $limitSize?->limit;
        $allRemainFileSpace = null;
        $allConsumedFileSpace = null;
        if($allFileSpace != null)
        {
            $allRemainFileSpace= abs( $limitSize->actual_limit);
            $allConsumedFileSpace= abs($limitSize->limit - $limitSize->actual_limit);
        }


        $widgets = [
            'total_files_count' => $this->repository->getTotalFilesCount(),
            'expired_files_count' => $expiredFilesCount,
            'expired_files_percentage' => $filesData['count'] !=0?($expiredFilesCount/$filesData['count'])*100:0,
            'valid_files_count' => $validFilesCount,
            'valid_files_percentage' => $filesData['count'] !=0?($validFilesCount/$filesData['count'])*100:0,
            'almost_expired_files_count' => $almostExpiredFilesCount,
            'almost_expired_files_percentage' => $filesData['count'] !=0?($almostExpiredFilesCount/$filesData['count'])*100:0,
            'almost_expired_files' => $this->repository->getAlmostExpiredFiles($folderId),
            "all_file_space"=>$allFileSpace,
            "all_remain_file_space"=>$allRemainFileSpace,
            "all_consumed_file_space"=>$allConsumedFileSpace
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

    public function shareFile(array $fileIds, array $userIds): array
    {
        $url = request()->header("X-DOMAIN")??request()->host();

        $result = $this->repository->shareFile($fileIds, $userIds);

        // Generate share URLs for each file
        $shareUrls = [];
        foreach ($result['files'] as $file) {
            $shareUrls[] = $url. '/en/shared-file/' . $file->id;
        }

        return [
            'files' => $result['files'],
            'share_urls' => $shareUrls,
            'shared_with_count' => count($userIds),
            'files_count' => count($result['files']),
            'new_user_ids' => $result['new_user_ids'],
            'existing_user_ids' => $result['existing_user_ids'],
        ];
    }

    /**
     * Get files for export
     *
     * @param array $filters
     * @return Collection
     */
    public function getForExport(array $filters = []): Collection
    {
        return $this->repository->getForExport($filters);
    }
}

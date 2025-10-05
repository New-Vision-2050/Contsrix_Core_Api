<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Repositories;

use App\Exceptions\CustomException;
use BasePackage\Shared\Repositories\BaseRepository;
use DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Modules\ArchiveLibrary\File\Models\UserFilePermission;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\UuidInterface;
use Modules\ArchiveLibrary\File\Models\File;

/**
 * @property File $model
 * @method File findOneOrFail($id)
 * @method File findOneByOrFail(array $data)
 */
class FileRepository extends BaseRepository
{
    public function __construct(File $model, private FileUploadService $fileUploadService)
    {
        parent::__construct($model);
    }

    public function getFileList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getFile(UuidInterface $id): File
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createFile(array $data, UploadedFile $file): File
    {
        try {
            DB::beginTransaction();

            $fileModel = $this->create($data);
            $this->fileUploadService->uploadFile($fileModel, $file, "files", "upload", "public");

            DB::commit();

        } catch (\Exception $exception) {
            DB::rollBack();
            throw new CustomException($exception->getMessage());
        }
        return $fileModel;
    }

    public function updateFile(UuidInterface $id, array $data, $file): bool
    {
        try {
            DB::beginTransaction();
            $updated = $this->update($id, $data);
            $fileModel = $this->getFile($id);
            if ($file) {
                $fileModel->clearMediaCollection('upload');
                $this->fileUploadService->uploadFile($fileModel, $file, "files", "upload", "public");
            }

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            throw new CustomException($exception->getMessage());

        }
        return $updated;
    }

    public function deleteFile(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function attachUsers(File $file, array $userIds): void
    {
        foreach ($userIds as $userId) {
            UserFilePermission::create([
                'folder_id' => $file->folder_id ?? '',
                "user_id" => $userId,
                "file_id" => $file->id,
                'permission_type' => 'view',
            ]);
        }
    }

    public function syncUsers(File $file, array $userIds): void
    {
        $syncData = [];
        foreach ($userIds as $userId) {
            $syncData[$userId] = [
                'folder_id' => $file->folder_id ?? '',
                'permission_type' => 'view',
            ];
        }
        $file->users()->sync($syncData);
    }
}

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
}

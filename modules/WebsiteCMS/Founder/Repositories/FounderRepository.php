<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\Founder\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\WebsiteCMS\Founder\Models\Founder;
use App\Traits\HasExport;
use Modules\Shared\Media\Services\FileUploadService;
use Illuminate\Http\UploadedFile;

/**
 * @property Founder $model
 * @method Founder findOneOrFail($id)
 * @method Founder findOneByOrFail(array $data)
 */
class FounderRepository extends BaseRepository
{
    use HasExport;

    public function __construct(Founder $model, private FileUploadService $fileUploadService)
    {
        parent::__construct($model);
    }

    public function getFounderList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getFounder(UuidInterface $id): Founder
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createFounder(array $data, ?UploadedFile $personalPhoto = null): Founder
    {
        $data['company_id'] = tenant('id');
        $founder = $this->create($data);

        if ($personalPhoto) {
            $this->fileUploadService->uploadFile(
                $founder,
                $personalPhoto,
                'founder/personal-photo',
                'personal_photo',
                'public'
            );
        }

        return $founder->fresh();
    }

    public function updateFounder(UuidInterface $id, array $data, ?UploadedFile $personalPhoto = null): Founder
    {
        $this->update($id, $data);
        $founder = $this->find($id);

        if ($personalPhoto) {
            $founder->clearMediaCollection('personal_photo');
            $this->fileUploadService->uploadFile(
                $founder,
                $personalPhoto,
                'founder/personal-photo',
                'personal_photo',
                'public'
            );
        }

        return $founder->fresh();
    }

    public function deleteFounder(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function getCurrentCompanyFounders(int $limit = null): Collection
    {
        if($limit == null)
        {
            return $this->model
            ->where('company_id', tenant('id'))
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->get();
        }
        return $this->model
            ->where('company_id', tenant('id'))
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}

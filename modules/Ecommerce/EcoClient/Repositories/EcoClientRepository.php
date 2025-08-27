<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoClient\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoClient\Models\EcoClient;
use App\Traits\HasExport;
use Modules\Shared\Media\Services\FileUploadService;

/**
 * @property EcoClient $model
 * @method EcoClient findOneOrFail($id)
 * @method EcoClient findOneByOrFail(array $data)
 */
class EcoClientRepository extends BaseRepository
{
    use HasExport;

    public function __construct(
        EcoClient $model,
        private FileUploadService $fileUploadService,
    )
    {
        parent::__construct($model);
    }

    public function getEcoClientList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getEcoClient(UuidInterface $id): EcoClient
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createEcoClient(array $data): EcoClient
    {
        $ecoClient = $this->create($data);
        $profileImage = $data['profile_image'] ?? null;
        $path = $ecoClient->company->name . '/ecommerce/clients/' . $ecoClient->name;

        if ($profileImage) {
            $ecoClient->clearMediaCollection('eco_profile_client_image');

            $this->fileUploadService->uploadFile(
                $ecoClient,
                $profileImage,
                $path,
                'eco_profile_client_image',
                "public"
            );
        }
        return $ecoClient;
    }

    public function updateEcoClient(UuidInterface $id, array $data): bool
    {
        $this->update($id, $data);
        $ecoClient = $this->getEcoClient($id);

        $path = $ecoClient->company->name . '/ecommerce/clients/' . $ecoClient->name;

        $profileImage = $data['profile_image'] ?? null;

        if ($profileImage) {
            $ecoClient->clearMediaCollection('eco_profile_client_image');

            $this->fileUploadService->uploadFile(
                $ecoClient,
                $profileImage,
                $path,
                'eco_profile_client_image',
                "public"
            );
        }
        return true;
    }

    public function deleteEcoClient(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

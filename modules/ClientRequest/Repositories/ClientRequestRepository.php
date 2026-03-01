<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\UuidInterface;
use Modules\ClientRequest\Models\ClientRequest;
use App\Traits\HasExport;

/**
 * @property ClientRequest $model
 * @method ClientRequest findOneOrFail($id)
 * @method ClientRequest findOneByOrFail(array $data)
 */
class ClientRequestRepository extends BaseRepository
{
    use HasExport;

    public function __construct(
        ClientRequest $model,
        private FileUploadService $fileUploadService
    ) {
        parent::__construct($model);
    }

    public function getClientRequestList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getClientRequest(UuidInterface $id): ClientRequest
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createClientRequest(array $data, array $serviceIds = [], array $termSettingIds = [], array $attachments = []): ClientRequest
    {
        $clientRequest = $this->create($data);

        if (!empty($serviceIds)) {
            $clientRequest->services()->sync($serviceIds);
        }

        if (!empty($termSettingIds)) {
            $clientRequest->termSettings()->sync($termSettingIds);
        }

        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $this->fileUploadService->uploadFile(
                    $clientRequest,
                    $attachment,
                    'client-requests/attachments',
                    'attachments',
                    'public'
                );
            }
        }

        return $clientRequest->fresh(['services', 'termSettings', 'media']);
    }

    public function updateClientRequest(UuidInterface $id, array $data, array $serviceIds = [], array $termSettingIds = [], array $attachments = []): bool
    {
        $updated = $this->update($id->toString(), $data);

        if ($updated) {
            $clientRequest = $this->findOneOrFail($id);

            if (!empty($serviceIds)) {
                $clientRequest->services()->sync($serviceIds);
            }

            if (!empty($termSettingIds)) {
                $clientRequest->termSettings()->sync($termSettingIds);
            }

            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    $this->fileUploadService->uploadFile(
                        $clientRequest,
                        $attachment,
                        'client-requests/attachments',
                        'attachments',
                        'public'
                    );
                }
            }
        }

        return $updated;
    }

    public function deleteClientRequest(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

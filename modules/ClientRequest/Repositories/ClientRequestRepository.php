<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\UuidInterface;
use Modules\ClientRequest\Models\ClientRequest;
use Modules\ClientRequest\Models\ClientRequestServiceTerm;
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
        return $this->model
            ->with([
                'serviceTerms.termServiceSetting',
                'serviceTerms' => function ($query) {
                    // We'll load the term trees in the presenter using the term_ids
                }
            ])
            ->findOrFail($id->toString());
    }

    public function createClientRequest(array $data, array $serviceIds = [], array $termSettingIds = [], array $attachments = []): ClientRequest
    {
        $clientRequest = $this->create($data);

        if (!empty($serviceIds)) {
            $clientRequest->services()->sync($serviceIds);
        }

        // Handle the new term_setting_ids structure
        if (!empty($termSettingIds)) {
            // Check if it's the new structure (array of objects)
            if (isset($termSettingIds[0]['term_service_id']) && isset($termSettingIds[0]['term_ids'])) {
                // New structure: [{term_service_id: "", term_ids: []}]
                foreach ($termSettingIds as $termSetting) {
                    ClientRequestServiceTerm::create([
                        'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                        'client_request_id' => $clientRequest->id,
                        'term_service_setting_id' => $termSetting['term_service_id'],
                        'term_ids' => $termSetting['term_ids'],
                        'company_id' => $data['company_id'],
                    ]);
                    
                    // Also sync to the old relationship for backward compatibility
                    $clientRequest->termSettings()->syncWithoutDetaching($termSetting['term_ids']);
                }
            } else {
                // Old structure: simple array of IDs
                $clientRequest->termSettings()->sync($termSettingIds);
            }
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

        return $clientRequest->fresh(['services', 'termSettings' => function ($query) {
            $query->with(['parent.parent.parent.parent']);
        }, 'serviceTerms', 'media']);
    }

    public function updateClientRequest(UuidInterface $id, array $data, array $serviceIds = [], array $termSettingIds = [], array $attachments = []): bool
    {
        $updated = $this->update($id->toString(), $data);

        if ($updated) {
            $clientRequest = $this->findOneOrFail($id);

            if (!empty($serviceIds)) {
                $clientRequest->services()->sync($serviceIds);
            }

            // Handle the new term_setting_ids structure
            if (!empty($termSettingIds)) {
                // Delete existing service-term relationships
                $clientRequest->serviceTerms()->delete();
                
                // Check if it's the new structure (array of objects)
                if (isset($termSettingIds[0]['term_service_id']) && isset($termSettingIds[0]['term_ids'])) {
                    // New structure: [{term_service_id: "", term_ids: []}]
                    foreach ($termSettingIds as $termSetting) {
                        ClientRequestServiceTerm::create([
                            'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                            'client_request_id' => $clientRequest->id,
                            'term_service_setting_id' => $termSetting['term_service_id'],
                            'term_ids' => $termSetting['term_ids'],
                            'company_id' => $clientRequest->company_id,
                        ]);
                    }
                    
                    // Update the old relationship for backward compatibility
                    $allTermIds = [];
                    foreach ($termSettingIds as $termSetting) {
                        $allTermIds = array_merge($allTermIds, $termSetting['term_ids']);
                    }
                    $clientRequest->termSettings()->sync($allTermIds);
                } else {
                    // Old structure: simple array of IDs
                    $clientRequest->termSettings()->sync($termSettingIds);
                }
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

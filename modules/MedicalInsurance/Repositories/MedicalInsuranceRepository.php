<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\UuidInterface;
use Modules\MedicalInsurance\Models\MedicalInsurance;
use App\Traits\HasExport;

/**
 * @property MedicalInsurance $model
 * @method MedicalInsurance findOneOrFail($id)
 * @method MedicalInsurance findOneByOrFail(array $data)
 */
class MedicalInsuranceRepository extends BaseRepository
{
    use HasExport;

    public function __construct(
        MedicalInsurance $model,
        private FileUploadService $fileUploadService,
    ) {
        parent::__construct($model);
    }

    public function getMedicalInsuranceList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getMedicalInsurance(UuidInterface $id): MedicalInsurance
    {
        return $this->model->with(['employee', 'media'])->where('id', $id->toString())->firstOrFail();
    }

    public function createMedicalInsurance(array $data, array $attachments = []): MedicalInsurance
    {
        $medicalInsurance = $this->create($data);

        $this->uploadAttachments($medicalInsurance, $attachments, $data['company_id'] ?? tenant('id'));

        return $medicalInsurance->fresh(['employee', 'media']);
    }

    public function updateMedicalInsurance(
        UuidInterface $id,
        array $data,
        array $attachments = [],
        array $deletedAttachmentIds = [],
    ): bool {
        $updated = $this->update($id->toString(), $data);

        if ($updated) {
            $medicalInsurance = $this->findOneOrFail($id);

            foreach ($deletedAttachmentIds as $mediaId) {
                $medicalInsurance->deleteMedia($mediaId);
            }

            $this->uploadAttachments(
                $medicalInsurance,
                $attachments,
                $medicalInsurance->company_id ?? tenant('id')
            );
        }

        return $updated;
    }

    private function uploadAttachments(MedicalInsurance $medicalInsurance, array $attachments, ?string $companyId): void
    {
        if (empty($attachments)) {
            return;
        }

        $path = 'medical-insurance/' . $companyId;

        foreach ($attachments as $attachment) {
            $this->fileUploadService->uploadFile(
                $medicalInsurance,
                $attachment,
                $path,
                'attachments',
                'public'
            );
        }
    }

    public function deleteMedicalInsurance(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

<?php

declare(strict_types=1);

namespace Modules\UserInfo\ProfessionalCertificate\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\UserInfo\ProfessionalCertificate\Models\ProfessionalCertificate;

/**
 * @property ProfessionalCertificate $model
 * @method ProfessionalCertificate findOneOrFail($id)
 * @method ProfessionalCertificate findOneByOrFail(array $data)
 */
class ProfessionalCertificateRepository extends BaseRepository
{
    public function __construct(ProfessionalCertificate $model)
    {
        parent::__construct($model);
    }

    public function getProfessionalCertificateList(UuidInterface $companyId, UuidInterface $globalId, ?int $page, ?int $perPage = 10):array
    {
        return $this->paginated(
            ['company_id' => $companyId, 'global_id' => $globalId],
            $page,
            $perPage
        );
    }

    public function getProfessionalCertificate(UuidInterface $id): ProfessionalCertificate
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createProfessionalCertificate(array $data): ProfessionalCertificate
    {
        return $this->create($data);
    }

    public function updateProfessionalCertificate(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteProfessionalCertificate(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

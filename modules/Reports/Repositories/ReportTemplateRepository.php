<?php

declare(strict_types=1);

namespace Modules\Reports\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Reports\Models\ReportTemplate;
use Ramsey\Uuid\UuidInterface;

/**
 * @property ReportTemplate $model
 * @method ReportTemplate findOneOrFail($id)
 * @method ReportTemplate findOneByOrFail(array $data)
 */
class ReportTemplateRepository extends BaseRepository
{
    public function __construct(ReportTemplate $model)
    {
        parent::__construct($model);
    }

    public function getTemplate(UuidInterface $id): ReportTemplate
    {
        return $this->findOneByOrFail(['id' => $id->toString()]);
    }

    public function create(array $data): ReportTemplate
    {
        if (!isset($data['company_id'])) {
            $data['company_id'] = tenant('id');
        }

        return parent::create($data);
    }

    public function updateTemplate(UuidInterface $id, array $data): ReportTemplate
    {
        $this->update($id, $data);
        return $this->find($id);
    }

    public function deleteById(UuidInterface $id): bool
    {
        return (bool) $this->model->newQuery()
            ->where('id', $id->toString())
            ->delete();
    }
}

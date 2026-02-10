<?php

declare(strict_types=1);

namespace Modules\UserInfo\ContractualRelationship\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\UserInfo\ContractualRelationship\Models\ContractualRelationshipType;

class ContractualRelationshipTypeRepository extends BaseRepository
{
    public function __construct(ContractualRelationshipType $model)
    {
        parent::__construct($model);
    }

    public function getAllActiveTypes(): Collection
    {
        return $this->model->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();
    }

    public function getAllTypes(): Collection
    {
        return $this->model->orderBy('name', 'asc')->get();
    }
}

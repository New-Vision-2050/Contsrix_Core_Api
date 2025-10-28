<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Presenters;

use Modules\Ecommerce\Banner\Models\StoreBranch;
use BasePackage\Shared\Presenters\AbstractPresenter;

class StoreBranchPresenter extends AbstractPresenter
{
    private StoreBranch $storeBranch;

    public function __construct(StoreBranch $storeBranch)
    {
        $this->storeBranch = $storeBranch;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->storeBranch->id,
            'company_id' => $this->storeBranch->company_id,
            'type' => $this->storeBranch->type,
            'name' => $this->storeBranch->name,
            'country_id' => $this->storeBranch->country_id,
            'address' => $this->storeBranch->address,
            'phone' => $this->storeBranch->phone,
            'email' => $this->storeBranch->email,
            'latitude' => $this->storeBranch->latitude,
            'longitude' => $this->storeBranch->longitude,
            'is_active' => (int) $this->storeBranch->is_active,
            'full_address' => $this->storeBranch->full_address,
            'location' => $this->storeBranch->location,
            'company' => $this->storeBranch->company ? [
                'id' => $this->storeBranch->company->id,
                'name' => $this->storeBranch->company->name,
            ] : null,
            'country' => $this->storeBranch->country ? [
                'id' => $this->storeBranch->country->id,
                'name' => $this->storeBranch->country->name,
            ] : null,
            'created_at' => $this->storeBranch->created_at,
            'updated_at' => $this->storeBranch->updated_at,
        ];
    }
}

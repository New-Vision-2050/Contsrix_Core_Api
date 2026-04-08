<?php

declare(strict_types=1);

namespace Modules\Shared\ResourceShare\Presenters;

use Modules\Shared\ResourceShare\Models\ResourceShare;

class ResourceSharePresenter
{
    public function __construct(private ResourceShare $share)
    {
    }

    public function getData(): array
    {
        return [
            'id' => $this->share->id,
            'shareable_type' => $this->share->shareable_type,
            'shareable_id' => $this->share->shareable_id,
            'owner_company' => $this->share->ownerCompany ? [
                'id' => $this->share->ownerCompany->id,
                'name' => $this->share->ownerCompany->name,
                'serial_number' => $this->share->ownerCompany->serial_number,
            ] : null,
            'shared_with_company' => $this->share->sharedWithCompany ? [
                'id' => $this->share->sharedWithCompany->id,
                'name' => $this->share->sharedWithCompany->name,
                'serial_number' => $this->share->sharedWithCompany->serial_number,
            ] : null,
            'status' => $this->share->status,
            'schema_ids' => $this->share->schema_ids,
            'shared_by' => $this->share->sharedByUser ? [
                'id' => $this->share->sharedByUser->id,
                'name' => $this->share->sharedByUser->name,
            ] : null,
            'responded_by' => $this->share->respondedByUser ? [
                'id' => $this->share->respondedByUser->id,
                'name' => $this->share->respondedByUser->name,
            ] : null,
            'responded_at' => $this->share->responded_at?->toISOString(),
            'notes' => $this->share->notes,
            'created_at' => $this->share->created_at?->toISOString(),
            'updated_at' => $this->share->updated_at?->toISOString(),
        ];
    }
}

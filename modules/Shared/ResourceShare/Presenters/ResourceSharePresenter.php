<?php

declare(strict_types=1);

namespace Modules\Shared\ResourceShare\Presenters;

use Modules\Company\CompanyCore\Models\Company;
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
            'owner_company' => $this->companyData($this->share->ownerCompany),
            'shared_with_company' => $this->companyData($this->share->sharedWithCompany),
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
            'shareable' => $this->share->shareable ? [
                'id' => $this->share->shareable->id,
                'name' => $this->share->shareable->name,
                'serial_number' => $this->share->shareable->serial_number,
            ] : null,
            'type' => $this->share->type ? [
                'id' => $this->share->type->id,
                'name' => $this->share->type->name,
            ] : null,
            'relation' => $this->share->relation ? [
                'id' => $this->share->relation->id,
                'name' => $this->share->relation->name,
            ] : null,
            'role' => $this->share->role ? [
                'id' => $this->share->role->id,
                'name' => $this->share->role->name,
            ] : null,
            'created_at' => $this->share->created_at?->toISOString(),
            'updated_at' => $this->share->updated_at?->toISOString(),
        ];
    }

    public static function companyData(?Company $company): ?array
    {
        if (! $company) {
            return null;
        }

        $mainBranch = $company->relationLoaded('mainBranch')
            ? $company->mainBranch
            : $company->mainBranch()->first();

        return [
            'id' => $company->id,
            'name' => $company->name,
            'serial_no' => $company->serial_no,
            'serial_number' => $company->serial_no,
            'email' => $company->email ?: $mainBranch?->email,
            'phone' => $company->phone ?: $mainBranch?->phone,
            'phone_code' => $mainBranch?->phone_code,
        ];
    }
}

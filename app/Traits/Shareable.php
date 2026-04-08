<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Shared\ResourceShare\Models\ResourceShare;

trait Shareable
{
    /**
     * Boot the trait
     */
    public static function bootShareable(): void
    {
        // Apply global scope to include owned and accepted shared resources
        static::addGlobalScope('shareable', function (Builder $builder) {
            $companyId = tenant('id');
            
            if ($companyId) {
                $builder->where(function ($query) use ($companyId) {
                    // Resources owned by current company
                    $query->where('company_id', $companyId)
                        // OR resources shared with current company (accepted)
                        ->orWhereHas('acceptedShares', function ($shareQuery) use ($companyId) {
                            $shareQuery->where('shared_with_company_id', $companyId)
                                ->where('status', 'accepted');
                        });
                });
            }
        });
    }

    /**
     * Get the tenant ID column name
     */
    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    /**
     * Get all shares for this resource
     */
    public function shares(): MorphMany
    {
        return $this->morphMany(ResourceShare::class, 'shareable');
    }

    /**
     * Get only accepted shares
     */
    public function acceptedShares(): MorphMany
    {
        return $this->morphMany(ResourceShare::class, 'shareable')
            ->where('status', 'accepted');
    }

    /**
     * Get only pending shares
     */
    public function pendingShares(): MorphMany
    {
        return $this->morphMany(ResourceShare::class, 'shareable')
            ->where('status', 'pending');
    }

    /**
     * Get only rejected shares
     */
    public function rejectedShares(): MorphMany
    {
        return $this->morphMany(ResourceShare::class, 'shareable')
            ->where('status', 'rejected');
    }

    /**
     * Check if resource is owned by current company
     */
    public function isOwnedByCurrentCompany(): bool
    {
        return $this->company_id === tenant('id');
    }

    /**
     * Check if resource is shared with specific company
     */
    public function isSharedWith(string $companyId): bool
    {
        return $this->shares()
            ->where('shared_with_company_id', $companyId)
            ->where('status', 'accepted')
            ->exists();
    }

    /**
     * Share this resource with a company
     */
    public function shareWith(string $companyId, ?array $schemaIds = null, ?string $userId = null, ?string $notes = null): ResourceShare
    {
        return ResourceShare::create([
            'shareable_type' => get_class($this),
            'shareable_id' => $this->id,
            'owner_company_id' => $this->company_id,
            'shared_with_company_id' => $companyId,
            'status' => 'pending',
            'schema_ids' => $schemaIds,
            'shared_by_user_id' => $userId,
            'notes' => $notes,
        ]);
    }

    /**
     * Get sharing status for specific company
     */
    public function getSharingStatus(string $companyId): ?string
    {
        $share = $this->shares()
            ->where('shared_with_company_id', $companyId)
            ->first();

        return $share?->status;
    }

    /**
     * Scope to get only owned resources
     */
    public function scopeOwnedOnly(Builder $query): Builder
    {
        return $query->withoutGlobalScope('shareable')
            ->where('company_id', tenant('id'));
    }

    /**
     * Scope to get only shared resources
     */
    public function scopeSharedOnly(Builder $query): Builder
    {
        return $query->withoutGlobalScope('shareable')
            ->whereHas('acceptedShares', function ($shareQuery) {
                $shareQuery->where('shared_with_company_id', tenant('id'))
                    ->where('status', 'accepted');
            });
    }
}

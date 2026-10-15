<?php

declare(strict_types=1);

namespace Modules\Advance\AdvancePolicy\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Advance\AdvancePolicy\DTO\AdvancePolicyDTO;
use Modules\Advance\AdvancePolicy\Models\AdvancePolicy;
use Ramsey\Uuid\Uuid;

class AdvancePolicyCRUDService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 10)));

        return AdvancePolicy::query()
            ->where('company_id', (string) tenant('id'))
            ->when($filters['name'] ?? null, fn ($q, $name) => $q->where('name', 'like', '%' . $name . '%'))
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', (int) ($filters['page'] ?? 1));
    }

    public function get(string $id): AdvancePolicy
    {
        return AdvancePolicy::query()
            ->where('company_id', (string) tenant('id'))
            ->findOrFail($id);
    }

    public function create(AdvancePolicyDTO $dto): AdvancePolicy
    {
        return AdvancePolicy::query()->create([
            'id' => Uuid::uuid4()->toString(),
            'company_id' => (string) tenant('id'),
            'name' => $dto->name,
            'max_amount' => $dto->max_amount,
            'max_installments' => $dto->max_installments,
            'max_active_advances' => $dto->max_active_advances,
            'requires_attachment' => $dto->requires_attachment,
        ]);
    }

    public function update(string $id, AdvancePolicyDTO $dto): AdvancePolicy
    {
        $policy = $this->get($id);

        $policy->update([
            'name' => $dto->name,
            'max_amount' => $dto->max_amount,
            'max_installments' => $dto->max_installments,
            'max_active_advances' => $dto->max_active_advances,
            'requires_attachment' => $dto->requires_attachment,
        ]);

        return $policy->fresh();
    }

    public function delete(string $id): void
    {
        $this->get($id)->delete();
    }
}

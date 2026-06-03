<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use Modules\MedicalInsurance\Models\MedicalInsuranceSubscription;
use Modules\MedicalInsurance\Models\MedicalInsuranceSubscriptionFamilyMember;
use Ramsey\Uuid\UuidInterface;

/**
 * @property MedicalInsuranceSubscription $model
 */
class MedicalInsuranceSubscriptionRepository extends BaseRepository
{
    public function __construct(MedicalInsuranceSubscription $model)
    {
        parent::__construct($model);
    }

    public function createWithFamilyMembers(array $data, array $familyMembers): MedicalInsuranceSubscription
    {
        return DB::transaction(function () use ($data, $familyMembers) {
            $subscription = $this->create($data);

            if (!empty($familyMembers)) {
                $now = now();
                $rows = array_map(fn (array $member) => array_merge($member, [
                    'id'                                => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                    'medical_insurance_subscription_id' => $subscription->id,
                    'created_at'                        => $now,
                    'updated_at'                        => $now,
                ]), $familyMembers);

                MedicalInsuranceSubscriptionFamilyMember::insert($rows);
            }

            return $subscription->load(['user', 'medicalInsurance', 'category', 'familyMembers']);
        });
    }

    public function getSubscription(UuidInterface $id): MedicalInsuranceSubscription
    {
        return $this->model
            ->with(['user', 'medicalInsurance', 'category', 'familyMembers'])
            ->where('id', $id->toString())
            ->firstOrFail();
    }

    public function listSubscriptions(int $page, int $perPage, array $filters = []): array
    {
        $query = $this->model->with(['user', 'medicalInsurance', 'category', 'familyMembers']);

        $query->whereNotIn('user_id', function ($q) {
            $q->select('u.id')
                ->from('users as u')
                ->join('user_privileges as up', 'u.global_company_user_id', '=', 'up.global_id')
                ->where('up.type_allowance_code', 'constant')
                ->whereNull('up.deleted_at');
        });

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['user_ids'])) {
            $query->whereIn('user_id', $filters['user_ids']);
        }

        if (!empty($filters['medical_insurance_id'])) {
            $query->where('medical_insurance_id', $filters['medical_insurance_id']);
        }

        if (!empty($filters['medical_insurance_category_id'])) {
            $query->where('medical_insurance_category_id', $filters['medical_insurance_category_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $total = $query->count();
        $data  = $query->orderBy('created_at', 'desc')->forPage($page, $perPage)->get();

        return [
            'data'       => $data,
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / max($perPage, 1)),
            ],
        ];
    }

    public function replaceFamilyMembers(UuidInterface $subscriptionId, array $members): void
    {
        MedicalInsuranceSubscriptionFamilyMember::where('medical_insurance_subscription_id', $subscriptionId->toString())
            ->delete();

        if (!empty($members)) {
            $now  = now();
            $rows = array_map(fn (array $member) => array_merge($member, [
                'id'                                => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'medical_insurance_subscription_id' => $subscriptionId->toString(),
                'created_at'                        => $now,
                'updated_at'                        => $now,
            ]), $members);

            MedicalInsuranceSubscriptionFamilyMember::insert($rows);
        }
    }

    public function deleteByUserAndInsurance(string $userId, string $medicalInsuranceId): void
    {
        $subscriptionIds = $this->model
            ->where('user_id', $userId)
            ->where('medical_insurance_id', $medicalInsuranceId)
            ->pluck('id')
            ->toArray();

        if (empty($subscriptionIds)) {
            return;
        }

        MedicalInsuranceSubscriptionFamilyMember::whereIn('medical_insurance_subscription_id', $subscriptionIds)
            ->delete();

        $this->model
            ->whereIn('id', $subscriptionIds)
            ->update(['subscription_no' => DB::raw('CONCAT("DELETED_", id)')]);

        $this->model
            ->whereIn('id', $subscriptionIds)
            ->delete();
    }

    public function updateSubscription(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteSubscription(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

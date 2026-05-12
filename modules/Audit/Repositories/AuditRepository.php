<?php

declare(strict_types=1);

namespace Modules\Audit\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Audit\Models\Audit;

/**
 * @property Audit $model
 * @method Audit findOneOrFail($id)
 * @method Audit findOneByOrFail(array $data)
 */
class AuditRepository extends BaseRepository
{
    public function __construct(Audit $model)
    {
        parent::__construct($model);
    }

    public function getAuditList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function groupedBy()
    {
        $limit = request()->has('limit') ? request()->limit : 10;
        return Audit::query()->orderByDesc('id')
            ->whereNotIn('auditable_type', [
                'Modules\\Attendance\\Models\\Attendance',
                'Modules\\Attendance\\Models\\AppliedAttendanceConstraint',
            ])
            ->when(request()->has('user_id')&& request()->user_id !="", function ($q) {

                $q->where('user_id', request()->user_id);
            })
            ->when(request()->has('type') && request()->type !=null && request()->type !="", function ($q) {
                $q->where('event', request()->type);
            })

            ->when((!auth()->user()->hasRole("super-admin"))&&(!auth()->user()->hasRole("admin"))&&(!auth()->user()->is_owner), function ($q) {
                $q->where('user_id', auth()->user()->id);
            })
            ->when(request()->has('time_from') && request()->time_from !=null && request()->time_from !=""  , function ($q) {
                $q->whereDate('created_at', '>=', request()->time_from);
            })
            ->when(request()->has('time_to')&& request()->time_to !=null && request()->time_to !="" , function ($q) {
                $q->whereDate('created_at', '<=', request()->time_to);
            })
            ->paginate($limit)->getCollection()->groupBy(fn($pv) => $pv->created_at->format('Y-m-d'));
    }

    public function getAudit(UuidInterface $id): Audit
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createAudit(array $data): Audit
    {
        return $this->create($data);
    }

    public function updateAudit(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteAudit(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

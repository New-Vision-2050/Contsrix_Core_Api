<?php

declare(strict_types=1);

namespace Modules\Reports\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Reports\Enums\ReportStatus;
use Modules\Reports\Models\Report;
use Ramsey\Uuid\UuidInterface;

/**
 * @property Report $model
 * @method Report findOneOrFail($id)
 * @method Report findOneByOrFail(array $data)
 */
class ReportRepository extends BaseRepository
{
    public function __construct(Report $model)
    {
        parent::__construct($model);
    }

    public function paginated(
        array  $conditions = [],
        int    $page       = 1,
        int    $perPage    = 15,
        string $orderBy    = 'created_at',
        string $sortBy     = 'desc',
        array  $filters    = [],
    ): array {
        $query = $this->model->newQuery()
            ->where('company_id', tenant('id'))
            ->orderBy($orderBy, $sortBy);

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['period_type'])) {
            $query->where('period_type', $filters['period_type']);
        }
        if (!empty($filters['year'])) {
            $query->where('year', (int) $filters['year']);
        }
        if (!empty($filters['month'])) {
            $query->where('month', (int) $filters['month']);
        }
        if (!empty($filters['template_id'])) {
            $query->where('template_id', $filters['template_id']);
        }
        if (!empty($filters['report_type'])) {
            $query->whereJsonContains('report_types', $filters['report_type']);
        }
        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('serial_number', 'like', "%{$term}%")
                  ->orWhereRaw('LOWER(JSON_EXTRACT(name, \"$.*\")) LIKE ?', ["%" . strtolower($term) . "%"]);
            });
        }

        $total = $query->count();
        $data  = (clone $query)->forPage($page, $perPage)->get();

        return [
            'data'       => $data,
            'pagination' => $this->getPaginationInformation($page, $perPage, $total)['pagination'],
        ];
    }

    public function getReport(UuidInterface $id): Report
    {
        return $this->findOneByOrFail(['id' => $id->toString()]);
    }

    public function create(array $data): Report
    {
        if (!isset($data['company_id'])) {
            $data['company_id'] = tenant('id');
        }
        if (!isset($data['status'])) {
            $data['status'] = ReportStatus::PENDING;
        }

        return parent::create($data);
    }

    public function markProcessing(UuidInterface $id): void
    {
        $this->model->newQuery()
            ->where('id', $id->toString())
            ->update([
                'status'        => ReportStatus::PROCESSING,
                'error_message' => null,
                'updated_at'    => now(),
            ]);
    }

    public function markReady(
        UuidInterface $id,
        string $filePath,
        string $fileDisk,
        ?int $fileSize = null
    ): void {
        $this->model->newQuery()
            ->where('id', $id->toString())
            ->update([
                'status'        => ReportStatus::READY,
                'file_path'     => $filePath,
                'file_disk'     => $fileDisk,
                'file_size'     => $fileSize,
                'generated_at'  => now(),
                'error_message' => null,
                'updated_at'    => now(),
            ]);
    }

    public function markFailed(UuidInterface $id, string $errorMessage): void
    {
        $this->model->newQuery()
            ->where('id', $id->toString())
            ->update([
                'status'        => ReportStatus::FAILED,
                'error_message' => substr($errorMessage, 0, 60_000),
                'updated_at'    => now(),
            ]);
    }

    public function generateSerialNumber(): string
    {
        $year      = Carbon::now()->format('Y');
        $companyId = tenant('id');
        $prefix    = "REP-{$year}-";

        $max = DB::table('reports')
            ->where('company_id', $companyId)
            ->where('serial_number', 'like', $prefix . '%')
            ->max(DB::raw('CAST(SUBSTRING(serial_number, ' . (strlen($prefix) + 1) . ') AS UNSIGNED)'));

        $sequence = ((int) $max) + 1;

        return $prefix . str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    public function deleteById(UuidInterface $id): bool
    {
        return DB::transaction(function () use ($id) {
            return (bool) $this->model->newQuery()
                ->where('id', $id->toString())
                ->delete();
        });
    }
}

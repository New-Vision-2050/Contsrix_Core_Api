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

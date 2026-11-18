<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\User\Models\User;

final class ProjectWorkOrderExcelImport extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_COMPLETED_WITH_ERRORS = 'completed_with_errors';

    public const STATUS_FAILED = 'failed';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'project_work_order_excel_imports';

    protected $fillable = [
        'id',
        'project_id',
        'company_id',
        'user_id',
        'status',
        'original_filename',
        'stored_path',
        'file_size',
        'mime_type',
        'total_rows',
        'processed_rows',
        'updated_rows',
        'skipped_rows',
        'row_errors',
        'row_warnings',
        'failure_reason',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'updated_rows' => 'integer',
        'skipped_rows' => 'integer',
        'row_errors' => 'array',
        'row_warnings' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectManagement::class, 'project_id')->withoutGlobalScopes();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->withoutGlobalScopes();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withoutGlobalScopes();
    }

    /**
     * @return array<string, mixed>
     */
    public function toStatusPayload(): array
    {
        return [
            'import_id' => $this->id,
            'project_id' => $this->project_id,
            'company_id' => $this->company_id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'original_filename' => $this->original_filename,
            'total_rows' => $this->total_rows,
            'processed_rows' => $this->processed_rows,
            'updated_rows' => $this->updated_rows,
            'skipped_rows' => $this->skipped_rows,
            'row_errors' => $this->row_errors ?? [],
            'row_warnings' => $this->row_warnings ?? [],
            'failure_reason' => $this->failure_reason,
            'started_at' => $this->started_at?->toDateTimeString(),
            'finished_at' => $this->finished_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}

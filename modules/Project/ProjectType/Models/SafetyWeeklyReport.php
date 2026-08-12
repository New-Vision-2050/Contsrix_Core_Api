<?php

namespace Modules\Project\ProjectType\Models;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\User\Models\User;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SafetyWeeklyReport extends Model implements HasMedia
{
    use UuidTrait;
    use CustomBelongsToTenant;
    use InteractsWithMedia;

    public const STATUS_PENDING = 'pending';

    public const STATUS_READY = 'ready';

    public const STATUS_FAILED = 'failed';

    public const MEDIA_COLLECTION = 'weekly_report_file';

    protected $table = 'safety_weekly_reports';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'project_id',
        'created_by',
        'serial_number',
        'name',
        'from_date',
        'to_date',
        'status',
        'file_path',
        'file_disk',
        'file_size',
        'generated_at',
        'error_message',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'project_id' => 'string',
        'created_by' => 'string',
        'from_date' => 'date',
        'to_date' => 'date',
        'file_size' => 'integer',
        'generated_at' => 'datetime',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COLLECTION)->singleFile();
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectManagement::class, 'project_id')->withoutGlobalScopes();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->withoutGlobalScopes();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withoutGlobalScopes();
    }
}

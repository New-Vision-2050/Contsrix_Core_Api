<?php

declare(strict_types=1);

namespace Modules\Reports\Models;

use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Reports\Database\factories\ReportFactory;
use Modules\Reports\Enums\ReportStatus;
use Modules\User\Models\User;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * @property string $id
 * @property string $company_id
 * @property string|null $created_by
 * @property string|null $template_id
 * @property string|array $name
 * @property array $report_types
 * @property string $period_type
 * @property int $year
 * @property int|null $month
 * @property int|null $week
 * @property int|null $quarter
 * @property string|null $period_start
 * @property string|null $period_end
 * @property string $export_format
 * @property string $language
 * @property string $paper_size
 * @property string $print_orientation
 * @property array $config
 * @property string $status
 * @property string|null $file_path
 * @property string|null $file_disk
 * @property int|null $file_size
 * @property \Carbon\Carbon|null $generated_at
 * @property string|null $error_message
 */
class Report extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;
    use BelongsToTenant;

    protected $table = 'reports';

    public $incrementing = false;

    protected $keyType = 'string';

    public array $translatable = ['name'];

    protected $fillable = [
        'company_id',
        'created_by',
        'template_id',
        'name',
        'report_types',
        'period_type',
        'year',
        'month',
        'week',
        'quarter',
        'period_start',
        'period_end',
        'export_format',
        'language',
        'paper_size',
        'print_orientation',
        'config',
        'status',
        'file_path',
        'file_disk',
        'file_size',
        'generated_at',
        'error_message',
    ];

    protected $casts = [
        'id'            => 'string',
        'company_id'    => 'string',
        'created_by'    => 'string',
        'template_id'   => 'string',
        'report_types'  => 'array',
        'config'        => 'array',
        'year'          => 'integer',
        'month'         => 'integer',
        'week'          => 'integer',
        'quarter'       => 'integer',
        'file_size'     => 'integer',
        'period_start'  => 'date',
        'period_end'    => 'date',
        'generated_at'  => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'template_id');
    }

    public function isPending(): bool
    {
        return $this->status === ReportStatus::PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === ReportStatus::PROCESSING;
    }

    public function isReady(): bool
    {
        return $this->status === ReportStatus::READY;
    }

    public function isFailed(): bool
    {
        return $this->status === ReportStatus::FAILED;
    }

    protected static function newFactory(): ReportFactory
    {
        return ReportFactory::new();
    }
}

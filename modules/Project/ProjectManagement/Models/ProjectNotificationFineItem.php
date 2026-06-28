<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectNotificationFineItem extends Model
{
    use UuidTrait;

    protected $table = 'project_notification_fine_items';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'project_notification_fine_id',
        'name_ar',
        'name_en',
        'quantity',
        'unit_amount',
        'total_amount',
        'sort_order',
    ];

    protected $casts = [
        'id' => 'string',
        'quantity' => 'integer',
        'unit_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function fine(): BelongsTo
    {
        return $this->belongsTo(ProjectNotificationFine::class, 'project_notification_fine_id')->withoutGlobalScopes();
    }
}

<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Contractor extends Model
{
    use UuidTrait;
    use BelongsToTenant;

    protected $table = 'contractors';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'name',
        'number',
        'mobile',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'is_active' => 'boolean',
    ];

    public function projectNotifications(): HasMany
    {
        return $this->hasMany(ProjectNotification::class, 'contractor_id');
    }
}

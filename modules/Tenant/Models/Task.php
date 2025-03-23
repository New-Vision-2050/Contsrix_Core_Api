<?php

namespace Modules\Tenant\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\Tenant\Traits\BelongsToTenant;

class Task extends Model
{
    use HasFactory;
    use UuidTrait;
    use BelongsToTenant;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'name',
        'description',
        'due_date',
        'status',
        'assigned_to',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'project_id' => 'string',
        'due_date' => 'date',
    ];

    /**
     * Get the project that the task belongs to.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user that the task is assigned to.
     */
    public function assignee()
    {
        return $this->belongsTo(CompanyUser::class, 'assigned_to');
    }
}
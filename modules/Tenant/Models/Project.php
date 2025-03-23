<?php

namespace Modules\Tenant\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\Tenant\Traits\BelongsToTenant;

class Project extends Model
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
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        'company_user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the user that created the project.
     */
    public function creator()
    {
        return $this->belongsTo(CompanyUser::class, 'company_user_id');
    }

    /**
     * Get the tasks for the project.
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
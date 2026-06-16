<?php
// modules/EmployeeTask/Models/EmployeeTaskItem.php

declare(strict_types=1);

namespace Modules\EmployeeTask\Models;

use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeTaskItem extends Model
{
    use UuidTrait;
    use BaseFilterable;

    protected $table = 'employee_task_items';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'key', 'name', 'model_class'];

    public function tasks(): HasMany
    {
        return $this->hasMany(EmployeeTaskRequest::class, 'employee_task_item_id');
    }
}

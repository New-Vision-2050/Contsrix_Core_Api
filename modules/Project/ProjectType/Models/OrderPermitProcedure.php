<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderPermitProcedure extends Model
{
    protected $table = 'order_permit_procedure';

    protected $fillable = [
        'project_type_id',
        'code',
        'description',
    ];

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class, 'project_type_id');
    }

    public function reportForms(): HasMany
    {
        return $this->hasMany(ReportForm::class, 'order_permit_procedure_id');
    }
}

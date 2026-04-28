<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\CompanyCore\Models\Company;
use Modules\User\Models\User;

class ProcedureSettingStepConcernedUser extends Model
{
    protected $table = 'procedure_setting_step_concerned_users';

    protected $fillable = [
        'procedure_setting_step_id',
        'user_id',
        'company_id',
    ];

    protected $casts = [
        'procedure_setting_step_id' => 'integer',
    ];

    public function step(): BelongsTo
    {
        return $this->belongsTo(ProcedureSettingStep::class, 'procedure_setting_step_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}

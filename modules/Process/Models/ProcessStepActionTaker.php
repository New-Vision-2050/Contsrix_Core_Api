<?php

declare(strict_types=1);

namespace Modules\Process\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\User\Models\User;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;

class ProcessStepActionTaker extends Model
{




    protected $fillable = ['process_step_id', 'user_id'];




    public function procedureSettingStep(): BelongsTo
    {
        return $this->belongsTo(ProcedureSettingStep::class, 'step_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function actionByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_by');
    }

}

<?php

declare(strict_types=1);

namespace Modules\Forum\Models;

use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

class ForumActivityLog extends Model
{
    use CustomBelongsToTenant;
    use UuidTrait;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'forum_id',
        'actor_id',
        'action',
        'target_type',
        'target_id',
        'target_label',
        'before',
        'after',
    ];

    protected $casts = [
        'id' => 'string',
        'before' => 'array',
        'after' => 'array',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class, 'forum_id');
    }
}

<?php

declare(strict_types=1);

namespace Modules\Forum\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

class ForumMember extends Model
{
    use UuidTrait;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'forum_id',
        'user_id',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class, 'forum_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

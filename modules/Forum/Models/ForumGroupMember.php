<?php

declare(strict_types=1);

namespace Modules\Forum\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Forum\Enums\ForumMemberRole;
use Modules\User\Models\User;

class ForumGroupMember extends Model
{
    use UuidTrait;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'group_id',
        'user_id',
        'role',
        'can_send',
        'last_read_at',
    ];

    protected $casts = [
        'id' => 'string',
        'role' => ForumMemberRole::class,
        'can_send' => 'boolean',
        'last_read_at' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ForumGroup::class, 'group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

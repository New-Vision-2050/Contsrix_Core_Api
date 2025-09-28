<?php

declare(strict_types=1);

namespace Modules\NotificationSettings\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\NotificationSettings\Database\factories\NotificationSettingsFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class NotificationSettings extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'user_id',
        'type',
        'email',
        'phone',
        'reminder_type',
        'message',
        'is_active',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'user_id' => 'string'
    ];

    /**
     * Validation rules for notification type
     */
    public static function getTypeOptions(): array
    {
        return ['mail', 'sms', 'both'];
    }

    /**
     * Validation rules for reminder type
     */
    public static function getReminderTypeOptions(): array
    {
        return ['daily', 'weekly'];
    }

    /**
     * Check if notification type is mail
     */
    public function isMail(): int
    {
        return $this->type === 'mail' || $this->type === 'both'?1:0;
    }

    /**
     * Check if notification type is SMS
     */
    public function isSms(): int
    {
        return $this->type === 'sms' || $this->type === 'both'?1:0;
    }

    /**
     * Check if reminder is daily
     */
    public function isDailyReminder(): int
    {
        return $this->reminder_type === 'daily'?1:0;
    }

    /**
     * Check if reminder is weekly
     */
    public function isWeeklyReminder(): int
    {
        return $this->reminder_type === 'weekly'?1:0;
    }

    protected static function newFactory(): NotificationSettingsFactory
    {
        return NotificationSettingsFactory::new();
    }
}

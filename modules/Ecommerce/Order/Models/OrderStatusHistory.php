<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use BasePackage\Shared\Traits\UuidTrait;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Modules\User\Models\User;

class OrderStatusHistory extends Model
{
    use HasFactory;
    use UuidTrait;
    use BelongsToTenant;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'order_status_histories';

    protected $fillable = [
        'company_id',
        'order_id',
        'user_type',
        'status',
        'previous_status',
        'changed_by',
        'reason',
        'notes',
        'changed_at',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'order_id' => 'string',
        'changed_by' => 'string',
        'changed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'في الانتظار',
            'confirmed' => 'مؤكد',
            'processing' => 'قيد المعالجة',
            'shipped' => 'تم الشحن',
            'out_for_delivery' => 'خرج للتوصيل',
            'delivered' => 'تم التوصيل',
            'returned' => 'مرتجع',
            'failed' => 'فشل',
            'canceled' => 'ملغي',
            'refunded' => 'مسترد',
            default => $this->status,
        };
    }

    public function getPreviousStatusLabelAttribute(): string
    {
        return match($this->previous_status) {
            'pending' => 'pending',
            'confirmed' => 'confirmed',
            'processing' => 'processing',
            'shipped' => 'shipped',
            'out_for_delivery' => 'out_for_delivery',
            'delivered' => 'delivered',
            'returned' => 'returned',
            'failed' => 'failed',
            'canceled' => 'canceled',
            'refunded' => 'refunded',
            default => $this->previous_status ?? 'غير محدد',
        };
    }

    public function getChangedAtFormattedAttribute(): string
    {
        return $this->changed_at ? $this->changed_at->format('Y-m-d H:i:s') : $this->created_at->format('Y-m-d H:i:s');
    }

    // Scopes
    public function scopeForOrder($query, string $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeChangedBy($query, string $userId)
    {
        return $query->where('changed_by', $userId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}

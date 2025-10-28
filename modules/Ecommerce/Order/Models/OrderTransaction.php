<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use BasePackage\Shared\Traits\UuidTrait;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class OrderTransaction extends Model
{
    use HasFactory;
    use UuidTrait;
    use BelongsToTenant;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'order_transactions';

    protected $fillable = [
        'company_id',
        'order_id',
        'transaction_id',
        'payment_method',
        'payment_gateway',
        'transaction_type',
        'amount',
        'currency',
        'status',
        'gateway_response',
        'reference_number',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'order_id' => 'string',
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Accessors
    public function getTransactionTypeLabelAttribute(): string
    {
        return match($this->transaction_type) {
            'payment' => 'دفع',
            'refund' => 'استرداد',
            'partial_refund' => 'استرداد جزئي',
            'chargeback' => 'استرداد قسري',
            'adjustment' => 'تعديل',
            default => $this->transaction_type,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'في الانتظار',
            'processing' => 'قيد المعالجة',
            'completed' => 'مكتمل',
            'failed' => 'فشل',
            'canceled' => 'ملغي',
            'refunded' => 'مسترد',
            'partially_refunded' => 'مسترد جزئياً',
            default => $this->status,
        };
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            'credit_card' => 'بطاقة ائتمان',
            'debit_card' => 'بطاقة خصم',
            'bank_transfer' => 'تحويل بنكي',
            'cash_on_delivery' => 'الدفع عند الاستلام',
            'wallet' => 'محفظة إلكترونية',
            'apple_pay' => 'Apple Pay',
            'google_pay' => 'Google Pay',
            'paypal' => 'PayPal',
            'stc_pay' => 'STC Pay',
            'mada' => 'مدى',
            default => $this->payment_method,
        };
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . strtoupper($this->currency ?? 'SAR');
    }

    public function getProcessedAtFormattedAttribute(): string
    {
        return $this->processed_at ? $this->processed_at->format('Y-m-d H:i:s') : 'غير محدد';
    }

    public function getIsSuccessfulAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getIsFailedAttribute(): bool
    {
        return in_array($this->status, ['failed', 'canceled']);
    }

    public function getIsRefundAttribute(): bool
    {
        return in_array($this->transaction_type, ['refund', 'partial_refund']);
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

    public function scopeByTransactionType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeByPaymentMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['failed', 'canceled']);
    }

    public function scopeRefunds($query)
    {
        return $query->whereIn('transaction_type', ['refund', 'partial_refund']);
    }

    public function scopePayments($query)
    {
        return $query->where('transaction_type', 'payment');
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByAmountRange($query, float $min, float $max)
    {
        return $query->whereBetween('amount', [$min, $max]);
    }
}

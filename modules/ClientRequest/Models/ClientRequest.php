<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\ClientRequest\Database\factories\ClientRequestFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Project\TermSetting\Models\TermSetting;
use Modules\User\Models\User;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ClientRequest extends Model implements HasMedia
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;
    use InteractsWithMedia;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ACCEPTED = 'accepted';

    public const PRICE_OFFER_STATUS_DRAFT = 'draft';
    public const PRICE_OFFER_STATUS_PENDING = 'pending';
    public const PRICE_OFFER_STATUS_REJECTED = 'rejected';
    public const PRICE_OFFER_STATUS_ACCEPTED = 'accepted';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $with = [
        'company',
        'client',
        'clientRequestType',
        'clientRequestReceiverFrom',
        'services',
        'termSettings',
        'branch',
        'management',
    ];

    protected $fillable = [
        'company_id',
        'client_request_type_id',
        'client_request_receiver_from_id',
        'client_type',
        'client_id',
        'content',
        'status_client_request',
        'client_price_offer_status',
        'branch_id',
        'management_id',
        'serial_number',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'client_id' => 'string',
        'status_client_request' => 'string',
        'client_price_offer_status' => 'string',
        'serial_number' => 'string',
    ];

    public function getTenantIdColumn(): string
    {
        return 'company_id';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function clientRequestType(): BelongsTo
    {
        return $this->belongsTo(ClientRequestType::class);
    }

    public function clientRequestReceiverFrom(): BelongsTo
    {
        return $this->belongsTo(ClientRequestReceiverFrom::class);
    }

    public function termSettings(): BelongsToMany
    {
        return $this->belongsToMany(
            TermSetting::class,
            'client_request_term_setting',
            'client_request_id',
            'term_setting_id'
        );
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(ManagementHierarchy::class, 'branch_id');
    }

    public function management(): BelongsTo
    {
        return $this->belongsTo(ManagementHierarchy::class, 'management_id');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(
            ClientRequestService::class,
            'client_request_service_pivot',
            'client_request_id',
            'client_request_service_id'
        );
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function isDraft(): bool
    {
        return $this->status_client_request === self::STATUS_DRAFT;
    }

    public function isPending(): bool
    {
        return $this->status_client_request === self::STATUS_PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status_client_request === self::STATUS_ACCEPTED;
    }

    public function isRejected(): bool
    {
        return $this->status_client_request === self::STATUS_REJECTED;
    }

    public function scopeDraft($query)
    {
        return $query->where('status_client_request', self::STATUS_DRAFT);
    }

    public function scopePending($query)
    {
        return $query->where('status_client_request', self::STATUS_PENDING);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status_client_request', self::STATUS_ACCEPTED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status_client_request', self::STATUS_REJECTED);
    }

    public function isPriceOfferDraft(): bool
    {
        return $this->client_price_offer_status === self::PRICE_OFFER_STATUS_DRAFT;
    }

    public function isPriceOfferPending(): bool
    {
        return $this->client_price_offer_status === self::PRICE_OFFER_STATUS_PENDING;
    }

    public function isPriceOfferAccepted(): bool
    {
        return $this->client_price_offer_status === self::PRICE_OFFER_STATUS_ACCEPTED;
    }

    public function isPriceOfferRejected(): bool
    {
        return $this->client_price_offer_status === self::PRICE_OFFER_STATUS_REJECTED;
    }

    public function scopePriceOfferDraft($query)
    {
        return $query->where('client_price_offer_status', self::PRICE_OFFER_STATUS_DRAFT);
    }

    public function scopePriceOfferPending($query)
    {
        return $query->where('client_price_offer_status', self::PRICE_OFFER_STATUS_PENDING);
    }

    public function scopePriceOfferAccepted($query)
    {
        return $query->where('client_price_offer_status', self::PRICE_OFFER_STATUS_ACCEPTED);
    }

    public function scopePriceOfferRejected($query)
    {
        return $query->where('client_price_offer_status', self::PRICE_OFFER_STATUS_REJECTED);
    }

    protected static function newFactory(): ClientRequestFactory
    {
        return ClientRequestFactory::new();
    }
}

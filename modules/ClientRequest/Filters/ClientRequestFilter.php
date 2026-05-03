<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;
use Illuminate\Support\Facades\Auth;
use Modules\ClientRequest\Enums\ProcessStepStatus;
use Modules\ClientRequest\Models\ClientRequest;

class ClientRequestFilter extends SearchModelFilter
{
    public $relations = [
        'clientRequestType',
        'clientRequestReceiverFrom',
        'services',
        'termSetting',
        'branch',
        'management',
    ];

    public function clientRequestTypeId($typeId)
    {
        return $this->where('client_request_type_id', $typeId);
    }

    public function clientRequestReceiverFromId($receiverId)
    {
        return $this->where('client_request_receiver_from_id', $receiverId);
    }

    public function clientType($clientType)
    {
        return $this->where('client_type', 'like', '%' . $clientType . '%');
    }

    public function clientId($clientId)
    {
        return $this->where('client_id', $clientId);
    }

    public function statusClientRequest($status)
    {
        return $this->where('status_client_request', $status);
    }

    public function content($content)
    {
        return $this->where('content', 'like', '%' . $content . '%');
    }

    public function termSettingId($termSettingId)
    {
        return $this->where('term_setting_id', $termSettingId);
    }

    public function branchId($branchId)
    {
        return $this->where('branch_id', $branchId);
    }

    public function managementId($managementId)
    {
        return $this->where('management_id', $managementId);
    }

    public function serviceId($serviceId)
    {
        return $this->whereHas('services', function ($query) use ($serviceId) {
            $query->where('client_request_services.id', $serviceId);
        });
    }

    public function dateFrom($dateFrom)
    {
        return $this->where('created_at', '>=', $dateFrom);
    }

    public function dateTo($dateTo)
    {
        return $this->where('created_at', '<=', $dateTo);
    }

    /**
     * Pending client requests the current user can act on (assigned on a pending process step).
     */
    public function pending(mixed $enabled = true)
    {
        $want = filter_var($enabled, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($want !== true) {
            return $this;
        }

        $this->where('status_client_request', ClientRequest::STATUS_PENDING);

        $userId = Auth::id();
        if ($userId === null) {
            return $this->whereRaw('0 = 1');
        }

        return $this->whereHas('clientRequestProcess', function ($processQuery) use ($userId) {
            $processQuery->whereHas('steps', function ($stepQuery) use ($userId) {
                $stepQuery
                    ->where('assigned_user_id', (string) $userId)
                    ->where('status', ProcessStepStatus::Pending->value);
            });
        });
    }

    public function accepted()
    {
        return $this->where('status_client_request', 'accepted');
    }

    public function rejected()
    {
        return $this->where('status_client_request', 'rejected');
    }

    public function clientPriceOfferStatus($status)
    {
        return $this->where('client_price_offer_status', $status);
    }

    public function priceOfferPending()
    {
        return $this->where('client_price_offer_status', 'pending');
    }

    public function priceOfferAccepted()
    {
        return $this->where('client_price_offer_status', 'accepted');
    }

    public function priceOfferRejected()
    {
        return $this->where('client_price_offer_status', 'rejected');
    }
}

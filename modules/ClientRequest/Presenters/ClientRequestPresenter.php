<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Presenters;

use Modules\ClientRequest\Models\ClientRequest;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ClientRequestPresenter extends AbstractPresenter
{
    private ClientRequest $clientRequest;

    public function __construct(ClientRequest $clientRequest)
    {
        $this->clientRequest = $clientRequest;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->clientRequest->id,
            'company_id' => $this->clientRequest->company_id,
            'client_request_type_id' => $this->clientRequest->client_request_type_id,
            'client_request_receiver_from_id' => $this->clientRequest->client_request_receiver_from_id,
            'client_type' => $this->clientRequest->client_type,
            'client_id' => $this->clientRequest->client_id,
            'content' => $this->clientRequest->content,
            'status_client_request' => $this->clientRequest->status_client_request,
            'client_price_offer_status' => $this->clientRequest->client_price_offer_status,
            'term_setting_id' => $this->clientRequest->term_setting_id,
            'branch_id' => $this->clientRequest->branch_id,
            'management_id' => $this->clientRequest->management_id,
            'created_at' => $this->clientRequest->created_at?->toDateTimeString(),
            'updated_at' => $this->clientRequest->updated_at?->toDateTimeString(),
        ];

        // Add status helpers
        $data['is_pending'] = $this->clientRequest->isPending();
        $data['is_accepted'] = $this->clientRequest->isAccepted();
        $data['is_rejected'] = $this->clientRequest->isRejected();
        
        // Add price offer status helpers
        $data['is_price_offer_pending'] = $this->clientRequest->isPriceOfferPending();
        $data['is_price_offer_accepted'] = $this->clientRequest->isPriceOfferAccepted();
        $data['is_price_offer_rejected'] = $this->clientRequest->isPriceOfferRejected();

        // Add company relationship
        if ($this->clientRequest->relationLoaded('company')) {
            $data['company'] = $this->clientRequest->company ? [
                'id' => $this->clientRequest->company->id,
                'name' => $this->clientRequest->company->name ?? null,
                'email' => $this->clientRequest->company->email ?? null,
                'phone' => $this->clientRequest->company->phone ?? null,
            ] : null;
        }

        // Add client request type relationship
        if ($this->clientRequest->relationLoaded('clientRequestType')) {
            $data['client_request_type'] = $this->clientRequest->clientRequestType ? [
                'id' => $this->clientRequest->clientRequestType->id,
                'name' => $this->clientRequest->clientRequestType->name,
                'type' => $this->clientRequest->clientRequestType->type,
                'is_active' => $this->clientRequest->clientRequestType->is_active ?? null,
                'created_at' => $this->clientRequest->clientRequestType->created_at?->toDateTimeString(),
            ] : null;
        }

        // Add client request receiver from relationship
        if ($this->clientRequest->relationLoaded('clientRequestReceiverFrom')) {
            $data['client_request_receiver_from'] = $this->clientRequest->clientRequestReceiverFrom ? [
                'id' => $this->clientRequest->clientRequestReceiverFrom->id,
                'name' => $this->clientRequest->clientRequestReceiverFrom->name,
                'type' => $this->clientRequest->clientRequestReceiverFrom->type,
                'is_active' => $this->clientRequest->clientRequestReceiverFrom->is_active ?? null,
                'created_at' => $this->clientRequest->clientRequestReceiverFrom->created_at?->toDateTimeString(),
            ] : null;
        }

        // Add services relationship
        if ($this->clientRequest->relationLoaded('services')) {
            $data['services'] = $this->clientRequest->services->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'type' => $service->type,
                    'is_active' => $service->is_active ?? null,
                    'created_at' => $service->created_at?->toDateTimeString(),
                ];
            })->toArray();
        }

        // Add term setting relationship
        if ($this->clientRequest->relationLoaded('termSetting')) {
            $data['term_setting'] = $this->clientRequest->termSetting ? [
                'id' => $this->clientRequest->termSetting->id,
                'name' => $this->clientRequest->termSetting->name ?? null,
                'description' => $this->clientRequest->termSetting->description ?? null,
                'is_active' => $this->clientRequest->termSetting->is_active ?? null,
                'created_at' => $this->clientRequest->termSetting->created_at?->toDateTimeString(),
            ] : null;
        }

        // Add branch relationship
        if ($this->clientRequest->relationLoaded('branch')) {
            $data['branch'] = $this->clientRequest->branch ? [
                'id' => $this->clientRequest->branch->id,
                'name' => $this->clientRequest->branch->name,
                'type' => $this->clientRequest->branch->type,
                'is_active' => $this->clientRequest->branch->is_active ?? null,
                'users_count' => $this->clientRequest->branch->users_count ?? 0,
                'created_at' => $this->clientRequest->branch->created_at?->toDateTimeString(),
            ] : null;
        }

        // Add management relationship
        if ($this->clientRequest->relationLoaded('management')) {
            $data['management'] = $this->clientRequest->management ? [
                'id' => $this->clientRequest->management->id,
                'name' => $this->clientRequest->management->name,
                'type' => $this->clientRequest->management->type,
                'is_active' => $this->clientRequest->management->is_active ?? null,
                'users_count' => $this->clientRequest->management->users_count ?? 0,
                'created_at' => $this->clientRequest->management->created_at?->toDateTimeString(),
            ] : null;
        }

        // Add media attachments
        if ($this->clientRequest->relationLoaded('media')) {
            $data['attachments'] = $this->clientRequest->getMedia('attachments')->map(function ($media) {
                return [
                    'id' => $media->id,
                    'name' => $media->name,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'human_readable_size' => $this->formatBytes($media->size),
                    'url' => $media->getUrl(),
                    'created_at' => $media->created_at?->toDateTimeString(),
                ];
            })->toArray();
        }

        return $data;
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

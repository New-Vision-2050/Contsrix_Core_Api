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
            'serial_number' => $this->clientRequest->serial_number,
            'company_id' => $this->clientRequest->company_id,
            'client_request_type_id' => $this->clientRequest->client_request_type_id,
            'client_request_receiver_from_id' => $this->clientRequest->client_request_receiver_from_id,
            'client_type' => $this->clientRequest->client_type,
            'client_id' => $this->clientRequest->client_id,
            'content' => $this->clientRequest->content,
            'status_client_request' => $this->clientRequest->status_client_request,
            'client_price_offer_status' => $this->clientRequest->client_price_offer_status,
            'branch_id' => $this->clientRequest->branch_id,
            'management_id' => $this->clientRequest->management_id,
            'created_at' => $this->clientRequest->created_at?->toDateTimeString(),
            'updated_at' => $this->clientRequest->updated_at?->toDateTimeString(),
        ];

        // Add company relationship
        $data['company'] = null;
        if ($this->clientRequest->relationLoaded('company') && $this->clientRequest->company) {
            $data['company'] = [
                'id' => $this->clientRequest->company->id,
                'name' => $this->clientRequest->company->name ?? null,
                'email' => $this->clientRequest->company->email ?? null,
                'phone' => $this->clientRequest->company->phone ?? null,
            ];
        }

        // Add client relationship
        $data['client'] = null;
        if ($this->clientRequest->relationLoaded('client') && $this->clientRequest->client) {
            $data['client'] = [
                'id' => $this->clientRequest->client->id,
                'name' => $this->clientRequest->client->name ?? null,
                'email' => $this->clientRequest->client->email ?? null,
                'phone' => $this->clientRequest->client->phone ?? null,
            ];
        }

        // Add client request type relationship
        $data['client_request_type'] = null;
        if ($this->clientRequest->relationLoaded('clientRequestType') && $this->clientRequest->clientRequestType) {
            $data['client_request_type'] = [
                'id' => $this->clientRequest->clientRequestType->id,
                'name' => $this->clientRequest->clientRequestType->name,
                'type' => $this->clientRequest->clientRequestType->type,
                'is_active' => $this->clientRequest->clientRequestType->is_active ?? null,
                'created_at' => $this->clientRequest->clientRequestType->created_at?->toDateTimeString(),
            ];
        }

        // Add client request receiver from relationship
        $data['client_request_receiver_from'] = null;
        if ($this->clientRequest->relationLoaded('clientRequestReceiverFrom') && $this->clientRequest->clientRequestReceiverFrom) {
            $data['client_request_receiver_from'] = [
                'id' => $this->clientRequest->clientRequestReceiverFrom->id,
                'name' => $this->clientRequest->clientRequestReceiverFrom->name,
                'type' => $this->clientRequest->clientRequestReceiverFrom->type,
                'is_active' => $this->clientRequest->clientRequestReceiverFrom->is_active ?? null,
                'created_at' => $this->clientRequest->clientRequestReceiverFrom->created_at?->toDateTimeString(),
            ];
        }

        // Add services relationship
        $data['services'] = [];
        if ($this->clientRequest->relationLoaded('services')&& $this->clientRequest->services) {
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

        // Add term settings relationship
        $data['term_settings'] = [];
        if ($this->clientRequest->relationLoaded('termSettings') && $this->clientRequest->termSettings) {
            $data['term_settings'] = $this->clientRequest->termSettings->map(function ($termSetting) {
                return [
                    'id' => $termSetting->id,
                    'name' => $termSetting->name ?? null,
                    'description' => $termSetting->description ?? null,
                    'is_active' => $termSetting->is_active ?? null,
                    'created_at' => $termSetting->created_at?->toDateTimeString(),
                ];
            })->toArray();
        }

        // Add branch relationship - always include even if null
        $data['branch'] = null;
        if ($this->clientRequest->relationLoaded('branch') && $this->clientRequest->branch) {
            $data['branch'] = [
                'id' => $this->clientRequest->branch->id,
                'name' => $this->clientRequest->branch->name,
                'type' => $this->clientRequest->branch->type,
                'is_active' => $this->clientRequest->branch->is_active ?? null,
                'users_count' => $this->clientRequest->branch->users_count ?? 0,
                'created_at' => $this->clientRequest->branch->created_at?->toDateTimeString(),
            ];
        }

        // Add management relationship - always include even if null
        $data['management'] = null;
        if ($this->clientRequest->relationLoaded('management') && $this->clientRequest->management) {
            $data['management'] = [
                'id' => $this->clientRequest->management->id,
                'name' => $this->clientRequest->management->name,
                'type' => $this->clientRequest->management->type,
                'is_active' => $this->clientRequest->management->is_active ?? null,
                'users_count' => $this->clientRequest->management->users_count ?? 0,
                'created_at' => $this->clientRequest->management->created_at?->toDateTimeString(),
            ];
        }

        // Add media attachments
        $data['attachments'] = [];
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

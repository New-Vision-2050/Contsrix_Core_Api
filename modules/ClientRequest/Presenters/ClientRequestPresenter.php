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
            'term_setting_id' => $this->clientRequest->term_setting_id,
            'branch_id' => $this->clientRequest->branch_id,
            'management_id' => $this->clientRequest->management_id,
            'created_at' => $this->clientRequest->created_at?->toDateTimeString(),
            'updated_at' => $this->clientRequest->updated_at?->toDateTimeString(),
        ];

        // Add relationships if loaded
        if ($this->clientRequest->relationLoaded('clientRequestType')) {
            $data['client_request_type'] = $this->clientRequest->clientRequestType ? [
                'id' => $this->clientRequest->clientRequestType->id,
                'name' => $this->clientRequest->clientRequestType->name,
                'type' => $this->clientRequest->clientRequestType->type,
            ] : null;
        }

        if ($this->clientRequest->relationLoaded('clientRequestReceiverFrom')) {
            $data['client_request_receiver_from'] = $this->clientRequest->clientRequestReceiverFrom ? [
                'id' => $this->clientRequest->clientRequestReceiverFrom->id,
                'name' => $this->clientRequest->clientRequestReceiverFrom->name,
                'type' => $this->clientRequest->clientRequestReceiverFrom->type,
            ] : null;
        }

        if ($this->clientRequest->relationLoaded('services')) {
            $data['services'] = $this->clientRequest->services->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'type' => $service->type,
                ];
            })->toArray();
        }

        if ($this->clientRequest->relationLoaded('termSetting')) {
            $data['term_setting'] = $this->clientRequest->termSetting ? [
                'id' => $this->clientRequest->termSetting->id,
                'name' => $this->clientRequest->termSetting->name ?? null,
            ] : null;
        }

        if ($this->clientRequest->relationLoaded('branch')) {
            $data['branch'] = $this->clientRequest->branch ? [
                'id' => $this->clientRequest->branch->id,
                'name' => $this->clientRequest->branch->name,
                'type' => $this->clientRequest->branch->type,
            ] : null;
        }

        if ($this->clientRequest->relationLoaded('management')) {
            $data['management'] = $this->clientRequest->management ? [
                'id' => $this->clientRequest->management->id,
                'name' => $this->clientRequest->management->name,
                'type' => $this->clientRequest->management->type,
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
                    'url' => $media->getUrl(),
                    'created_at' => $media->created_at?->toDateTimeString(),
                ];
            })->toArray();
        }

        // Add status helpers
        $data['client_request_status'] = $this->clientRequest->client_request_status;


        return $data;
    }
}

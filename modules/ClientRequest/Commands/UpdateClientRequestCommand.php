<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateClientRequestCommand
{
    public function __construct(
        private UuidInterface $id,
        private int $client_request_type_id,
        private int $client_request_receiver_from_id,
        private string $client_type,
        private string $client_id,
        private ?string $content = null,
        private ?string $receiver_phone = null,
        private ?string $receiver_email = null,
        private ?string $receiver_broker_type = null,
        private ?string $receiver_broker_id = null,
        private ?string $receiver_employee_id = null,
        private ?array $receiver_employee_ids = null,
        private ?string $reject_cause = null,
        private ?string $status_client_request = null,
        private ?string $client_price_offer_status = null,
        private ?int $branch_id = null,
        private ?int $management_id = null,
        private array $service_ids = [],
        private array $term_setting_ids = [],
        private array $attachments = [],
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getClientRequestTypeId(): int
    {
        return $this->client_request_type_id;
    }

    public function getClientRequestReceiverFromId(): int
    {
        return $this->client_request_receiver_from_id;
    }

    public function getClientType(): string
    {
        return $this->client_type;
    }

    public function getClientId(): string
    {
        return $this->client_id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getReceiverPhone(): ?string
    {
        return $this->receiver_phone;
    }

    public function getReceiverEmail(): ?string
    {
        return $this->receiver_email;
    }

    public function getReceiverBrokerType(): ?string
    {
        return $this->receiver_broker_type;
    }

    public function getReceiverBrokerId(): ?string
    {
        return $this->receiver_broker_id;
    }

    public function getReceiverEmployeeId(): ?string
    {
        return $this->receiver_employee_id;
    }

    public function getReceiverEmployeeIds(): ?array
    {
        return $this->receiver_employee_ids;
    }

    public function getRejectCause(): ?string
    {
        return $this->reject_cause;
    }

    public function getStatusClientRequest(): ?string
    {
        return $this->status_client_request;
    }

    public function getClientPriceOfferStatus(): ?string
    {
        return $this->client_price_offer_status;
    }

    public function getBranchId(): ?int
    {
        return $this->branch_id;
    }

    public function getManagementId(): ?int
    {
        return $this->management_id;
    }

    public function getServiceIds(): array
    {
        return $this->service_ids;
    }

    public function getTermSettingIds(): array
    {
        return $this->term_setting_ids;
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function toArray(): array
    {
        return [
            'client_request_type_id' => $this->client_request_type_id,
            'client_request_receiver_from_id' => $this->client_request_receiver_from_id,
            'client_type' => $this->client_type,
            'client_id' => $this->client_id,
            'content' => $this->content,
            'receiver_phone' => $this->receiver_phone,
            'receiver_email' => $this->receiver_email,
            'receiver_broker_type' => $this->receiver_broker_type,
            'receiver_broker_id' => $this->receiver_broker_id,
            'receiver_employee_id' => $this->receiver_employee_id,
            'receiver_employee_ids' => $this->receiver_employee_ids,
            'reject_cause' => $this->reject_cause,
            'status_client_request' => $this->status_client_request,
            'client_price_offer_status' => $this->client_price_offer_status,
            'branch_id' => $this->branch_id,
            'management_id' => $this->management_id,
        ];
    }
}

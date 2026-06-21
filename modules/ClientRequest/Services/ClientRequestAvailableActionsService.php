<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Services;

use Modules\ClientRequest\Models\ClientRequest;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Services\InternalProcedureAvailableActionsService;

/**
 * Thin wrapper around InternalProcedureAvailableActionsService for ClientRequest.
 * Resolves company/branch context from the client request and delegates
 * all filtering logic to the generic central service.
 */
final class ClientRequestAvailableActionsService
{
    public function __construct(
        private readonly InternalProcedureAvailableActionsService $actionsService,
    ) {}

    /**
     * @return list<array>
     */
    public function forClientRequest(string $clientRequestId): array
    {
        /** @var ClientRequest $cr */
        $cr = ClientRequest::query()->findOrFail($clientRequestId);

        $branchId = $cr->branch_id !== null ? (string) $cr->branch_id : null;

        return $this->actionsService->forProcessable(
            'client_request',
            $cr->id,
            ProcedureSettingType::ClientRequest->value,
            $cr->company_id,
            $branchId,
        );
    }
}

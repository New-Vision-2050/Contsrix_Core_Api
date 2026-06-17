<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\ClientRequest\Events\ClientRequestStatusChanged;
use Modules\ClientRequest\Models\ClientRequest;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Services\WorkflowEngine;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Enums\ProcessStepStatus;
use Modules\Process\Models\Process;
use Modules\Process\Models\ProcessStep;

class ClientRequestWorkflowService
{
    private const TYPE_CLIENT_REQUEST = 'client_request';

    public function __construct(
        private readonly WorkflowEngine $engine,
    ) {}

    public function startForClientRequest(ClientRequest $cr): ?Process
    {
        return $this->createProcessForClientRequest($cr);
    }

    public function createProcessForClientRequest(ClientRequest $cr): ?Process
    {
        return DB::transaction(function () use ($cr) {
            $existing = Process::query()
                ->where('processable_id', $cr->id)
                ->where('processable_type', self::TYPE_CLIENT_REQUEST)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $result = $this->engine->startWorkflow(
                processableType: self::TYPE_CLIENT_REQUEST,
                processableId: $cr->id,
                type: ProcedureSettingType::ClientRequest->value,
                formKey: null,
                companyId: $cr->company_id,
                branchId: $cr->branch_id !== null ? (string) $cr->branch_id : null,
                createdByUserId: $cr->created_by_user_id,
            );

            if ($result->autoApprove) {
                Log::warning('ClientRequestWorkflow: no procedure_settings found', [
                    'client_request_id' => $cr->id,
                    'company_id' => $cr->company_id,
                ]);

                return null;
            }

            return $result->activeProcess;
        });
    }

    private function initializeProcessSteps(Process $process): void
    {
        if ($process->steps()->exists()) {
            return;
        }
        $snapshots = $process->template_snapshot ?? [];
        if (empty($snapshots)) {
            return;
        }

        if ($process->execute_type === 'parallel') {
            foreach ($snapshots as $row) {
                $this->createProcessStepFromSnapshot($process, $row);
            }
        } else {
            $this->createProcessStepFromSnapshot($process, $snapshots[0]);
        }
    }

    public function syncAfterClientRequestStatusChange(ClientRequest $cr, string $newStatus): void
    {
        match ($newStatus) {
            ClientRequest::STATUS_PENDING => $this->createProcessForClientRequest($cr),
            ClientRequest::STATUS_ACCEPTED => $this->closeProcessOnClientRequestAccepted($cr),
            ClientRequest::STATUS_REJECTED => $this->closeProcessOnClientRequestRejected($cr),
            default => null,
        };
    }

    public function actOnProcessStepForClientRequest(string $clientRequestId, string $processStepId, string $action): void
    {
        $step = ProcessStep::query()->findOrFail($processStepId);
        $process = Process::query()->findOrFail($step->process_id);

        if ($process->processable_id !== $clientRequestId || $process->processable_type !== self::TYPE_CLIENT_REQUEST) {
            abort(404);
        }

        match ($action) {
            'approve' => $this->approve($processStepId),
            'reject' => $this->reject($processStepId),
            default => abort(422, 'Invalid process step action.'),
        };
    }

    public function actOnPendingStepForCurrentUser(string $clientRequestId, string $action): void
    {
        if (! Auth::check()) {
            abort(403);
        }

        $actorId = (string) Auth::id();

        $process = Process::query()
            ->where('processable_id', $clientRequestId)
            ->where('processable_type', self::TYPE_CLIENT_REQUEST)
            ->where('status', ProcessStatus::InProgress)
            ->first();

        if ($process === null) {
            abort(422, 'No active process found for this client request.');
        }

        $step = $this->findPendingStepForActor($process, $actorId);

        if ($step === null) {
            abort(422, 'No pending process step assigned to you for this client request.');
        }

        match ($action) {
            'approve' => $this->approve((string) $step->id),
            'reject' => $this->reject((string) $step->id),
            default => abort(422, 'Invalid process step action.'),
        };
    }

    public function approve(string $processStepId): ProcessStep
    {
        return DB::transaction(function () use ($processStepId) {
            /** @var ProcessStep $step */
            $step = ProcessStep::query()->whereKey($processStepId)->lockForUpdate()->firstOrFail();
            /** @var Process $process */
            $process = Process::query()->whereKey($step->process_id)->lockForUpdate()->firstOrFail();

            $this->assertActorCanActOnStep($step, $process);
            $this->assertStepIsPending($step);

            if ($process->status === ProcessStatus::Pending) {
                $process->update(['status' => ProcessStatus::InProgress]);
            }

            $actorId = (string) Auth::id();
            $step->update([
                'status' => ProcessStepStatus::Approved,
                'action_by' => $actorId,
                'acted_at' => now(),
            ]);

            $this->advanceProcessAfterAction($process);

            return $step->fresh();
        });
    }

    public function reject(string $processStepId): ProcessStep
    {
        return DB::transaction(function () use ($processStepId) {
            /** @var ProcessStep $step */
            $step = ProcessStep::query()->whereKey($processStepId)->lockForUpdate()->firstOrFail();
            /** @var Process $process */
            $process = Process::query()->whereKey($step->process_id)->lockForUpdate()->firstOrFail();

            $this->assertActorCanActOnStep($step, $process);
            $this->assertStepIsPending($step);

            $actorId = (string) Auth::id();
            $step->update([
                'status' => ProcessStepStatus::Rejected,
                'action_by' => $actorId,
                'acted_at' => now(),
            ]);

            $snapshotRow = $this->getSnapshotRowForStep($process, $step);
            $isJobRole = ($snapshotRow['specific_procedure_type'] ?? null) === 'job_role';

            if ($isJobRole) {
                $this->advanceProcessAfterAction($process);
            } else {
                $process->update(['status' => ProcessStatus::Failed]);
            }

            return $step->fresh();
        });
    }

    private function advanceProcessAfterAction(Process $process): void
    {
        if ($process->execute_type === 'sequence') {
            $snapshot = $process->template_snapshot ?? [];
            $approvedCount = $process->steps()->where('status', ProcessStepStatus::Approved)->count();
            $rejectedCount = $process->steps()->where('status', ProcessStepStatus::Rejected)->count();
            $actedCount = $approvedCount + $rejectedCount;

            if ($actedCount < count($snapshot)) {
                $nextRow = $snapshot[$actedCount];
                $this->createProcessStepFromSnapshot($process, $nextRow);
            } else {
                $process->update(['status' => ProcessStatus::Completed]);
                $this->moveToNextProcessOrFinalize($process);
            }
        } else {
            $total = $process->steps()->count();
            $acted = $process->steps()
                ->whereIn('status', [ProcessStepStatus::Approved, ProcessStepStatus::Rejected])
                ->count();

            if ($acted === $total && $total > 0) {
                $process->update(['status' => ProcessStatus::Completed]);
                $this->moveToNextProcessOrFinalize($process);
            }
        }
    }

    private function moveToNextProcessOrFinalize(Process $currentProcess): void
    {
        $nextProcess = Process::query()
            ->where('processable_id', $currentProcess->processable_id)
            ->where('processable_type', $currentProcess->processable_type)
            ->where('status', ProcessStatus::Pending)
            ->orderBy('sort_order')
            ->first();

        if ($nextProcess) {
            $nextProcess->update(['status' => ProcessStatus::InProgress]);
            $this->initializeProcessSteps($nextProcess);
        } else {
            $this->advanceClientRequestToPriceOfferAfterWorkflow((string) $currentProcess->processable_id);
        }
    }

    private function closeProcessOnClientRequestAccepted(ClientRequest $cr): void
    {
        DB::transaction(function () use ($cr) {
            $process = Process::query()
                ->where('processable_id', $cr->id)
                ->where('processable_type', self::TYPE_CLIENT_REQUEST)
                ->lockForUpdate()
                ->first();

            if ($process === null) {
                return;
            }

            if (! Auth::check()) {
                return;
            }

            $actorId = (string) Auth::id();
            $now = now();
            // Parallel: only the current user's assigned step(s) may be approved by this action.
            // Sequence: same — one actor closes only their pending slot(s); next slots are created via approve().
            $pendingSteps = ProcessStep::query()
                ->where('process_id', $process->id)
                ->where('status', ProcessStepStatus::Pending)
                ->get();

            $affected = 0;
            foreach ($pendingSteps as $pendingStep) {
                $authorizedUsers = $this->getAuthorizedUsersForStep($process, $pendingStep);

                if (in_array($actorId, $authorizedUsers, true)) {
                    $pendingStep->update([
                        'status' => ProcessStepStatus::Approved,
                        'action_by' => $actorId,
                        'acted_at' => $now,
                    ]);
                    $affected++;
                }
            }

            $process->refresh();

            // Sequence: same as approve() — after a step is approved, materialize the next snapshot row (if any).
            if ($affected > 0 && $process->execute_type === 'sequence') {
                $snapshot = $process->template_snapshot ?? [];
                $approvedCount = $process->steps()->where('status', ProcessStepStatus::Approved)->count();
                if (count($snapshot) > 0 && $approvedCount < count($snapshot)) {
                    $nextRow = $snapshot[$approvedCount];
                    $this->createProcessStepFromSnapshot($process, $nextRow);
                }
            }

            $stillPending = ProcessStep::query()
                ->where('process_id', $process->id)
                ->where('status', ProcessStepStatus::Pending)
                ->exists();

            if ($stillPending) {
                if ($process->status === ProcessStatus::Completed) {
                    $process->update(['status' => ProcessStatus::InProgress]);
                } elseif ($process->status === ProcessStatus::Pending) {
                    $process->update(['status' => ProcessStatus::InProgress]);
                }

                return;
            }

            if ($process->status !== ProcessStatus::Failed) {
                $process->update(['status' => ProcessStatus::Completed]);
            }

            $this->moveToNextProcessOrFinalize($process);
        });
    }

    private function closeProcessOnClientRequestRejected(ClientRequest $cr): void
    {
        DB::transaction(function () use ($cr) {
            $process = Process::query()
                ->where('processable_id', $cr->id)
                ->where('processable_type', self::TYPE_CLIENT_REQUEST)
                ->lockForUpdate()
                ->first();

            if ($process === null) {
                return;
            }

            if ($process->status === ProcessStatus::Failed) {
                return;
            }

            $actorId = Auth::check() ? (string) Auth::id() : null;

            ProcessStep::query()
                ->where('process_id', $process->id)
                ->where('status', ProcessStepStatus::Pending)
                ->update([
                    'status' => ProcessStepStatus::Rejected,
                    'action_by' => $actorId,
                    'acted_at' => now(),
                ]);

            Process::query()
                ->where('processable_id', $cr->id)
                ->where('processable_type', self::TYPE_CLIENT_REQUEST)
                ->where('status', '!=', ProcessStatus::Completed)
                ->update(['status' => ProcessStatus::Failed]);
        });
    }

    private function getSnapshotRowForStep(Process $process, ProcessStep $step): ?array
    {
        $snapshot = $process->template_snapshot ?? [];
        foreach ($snapshot as $row) {
            if ($row['step_id'] === $step->step_id) {
                return $row;
            }
        }

        return null;
    }

    private function getAuthorizedUsersForStep(Process $process, ProcessStep $step): array
    {
        if ($step->authorized_user_ids !== null) {
            return $step->authorized_user_ids;
        }

        $snapshotRow = $this->getSnapshotRowForStep($process, $step);

        return $snapshotRow !== null
            ? ($snapshotRow['authorized_user_ids'] ?? [$snapshotRow['assigned_user_id']])
            : [(string) $step->assigned_user_id];
    }

    private function findPendingStepForActor(Process $process, string $actorId): ?ProcessStep
    {
        $pendingSteps = ProcessStep::query()
            ->where('process_id', $process->id)
            ->where('status', ProcessStepStatus::Pending)
            ->get();

        foreach ($pendingSteps as $step) {
            $authorizedUsers = $this->getAuthorizedUsersForStep($process, $step);

            if (in_array($actorId, $authorizedUsers, true)) {
                return $step;
            }
        }

        return null;
    }

    /**
     * @param  array{step_id: int, template_step_order: ?int, assigned_user_id: string, escalation_management_hierarchy_id: ?int}  $row
     */
    private function createProcessStepFromSnapshot(Process $process, array $row): ProcessStep
    {
        return ProcessStep::query()->create([
            'process_id' => $process->id,
            'step_id' => $row['step_id'],
            'template_step_order' => $row['template_step_order'],
            'assigned_user_id' => $row['assigned_user_id'],
            'authorized_user_ids' => $row['authorized_user_ids'] ?? null,
            'escalation_management_hierarchy_id' => $row['escalation_management_hierarchy_id'],
            'status' => ProcessStepStatus::Pending,
        ]);
    }

    private function assertActorCanActOnStep(ProcessStep $step, Process $process): void
    {
        if (! Auth::check()) {
            abort(403);
        }

        $authorizedUsers = $this->getAuthorizedUsersForStep($process, $step);

        if (! in_array((string) Auth::id(), $authorizedUsers, true)) {
            abort(403);
        }
    }

    private function assertStepIsPending(ProcessStep $step): void
    {
        if ($step->status !== ProcessStepStatus::Pending) {
            abort(422, 'Process step is not pending.');
        }
    }

    /**
     * When the client_request workflow process completes (parallel: all steps approved;
     * sequence: last step approved), mark the request as accepted and open it for price-offer.
     */
    private function collectDescendantIds(string $parentId): array
    {
        $children = ProcedureSetting::query()
            ->where('parent_id', $parentId)
            ->pluck('id')
            ->all();

        $result = $children;
        foreach ($children as $childId) {
            $result = array_merge($result, $this->collectDescendantIds($childId));
        }

        return $result;
    }

    private function advanceClientRequestToPriceOfferAfterWorkflow(string $clientRequestId): void
    {
        $clientRequest = ClientRequest::query()
            ->whereKey($clientRequestId)
            ->lockForUpdate()
            ->first();

        if ($clientRequest === null) {
            return;
        }

        $updates = [
            'status_client_request' => ClientRequest::STATUS_ACCEPTED,
        ];

        $priceOfferStatus = $clientRequest->client_price_offer_status;
        if ($priceOfferStatus === null || $priceOfferStatus === '' || $priceOfferStatus === ClientRequest::PRICE_OFFER_STATUS_DRAFT) {
            $updates['client_price_offer_status'] = ClientRequest::PRICE_OFFER_STATUS_PENDING;
        }

        $clientRequest->update($updates);
        $clientRequest->refresh();

        $clientRequest->load(['company', 'createdByUser', 'receiverEmployees']);

        foreach ($clientRequest->receiverEmployees as $employee) {
            event(new ClientRequestStatusChanged(
                $clientRequest,
                ClientRequest::STATUS_ACCEPTED,
                (string) $employee->id,
            ));
        }
    }
}

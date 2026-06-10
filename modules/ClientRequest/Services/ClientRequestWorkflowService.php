<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Services;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Enums\ProcessStepStatus;
use Modules\ClientRequest\Events\ClientRequestStatusChanged;
use Modules\ClientRequest\Models\ClientRequest;
use Modules\Process\Models\Process;
use Modules\Process\Models\ProcessStep;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;

class ClientRequestWorkflowService
{
    private const TYPE_CLIENT_REQUEST = 'client_request';

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
            $settings = ProcedureSetting::query()
                ->where('type', ProcedureSettingType::ClientRequest->value)
                ->where('company_id', $cr->company_id)
                ->whereHas('workFlow', function ($q) use ($cr) {
                    $q->whereHas('managementHierarchies', function ($q) use ($cr) {
                        $q->where('management_hierarchies.id', $cr->branch_id);
                    });
                })
                ->orderBy('sort_order')
                ->get();

            if ($settings->isEmpty()) {
                Log::warning('ClientRequestWorkflow: no procedure_settings found', [
                    'client_request_id' => $cr->id,
                    'company_id'        => $cr->company_id,
                ]);
                return null;
            }

            $firstProcess = null;

            foreach ($settings as $index => $setting) {
                $exists = Process::query()
                    ->where('processable_id', $cr->id)
                    ->where('processable_type', self::TYPE_CLIENT_REQUEST)
                    ->where('sort_order', $setting->sort_order)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $steps = ProcedureSettingStep::query()
                    ->with(['actionTakers' => static fn ($q) => $q->orderBy('id')])
                    ->where('procedure_setting_id', $setting->id)
                    ->orderBy('step_order')
                    ->get();

                $snapshots = [];
                foreach ($steps as $step) {
                    if (! $step instanceof ProcedureSettingStep) {
                        continue;
                    }
                    $assignedUserId = $this->resolveAssignedUserId($step);
                    if ($assignedUserId === null) {
                        continue;
                    }
                    $snapshots[] = [
                        'step_id'                            => $step->id,
                        'template_step_order'                => $step->step_order,
                        'assigned_user_id'                   => $assignedUserId,
                        'escalation_management_hierarchy_id' => $step->escalation_management_hierarchy_id,
                    ];
                }

                $status = ($index === 0) ? ProcessStatus::InProgress : ProcessStatus::Pending;

                $process = Process::query()->create([
                    'processable_id'    => $cr->id,
                    'processable_type'  => self::TYPE_CLIENT_REQUEST,
                    'execute_type'      => $setting->execute_type,
                    'status'            => $status,
                    'template_snapshot' => $snapshots,
                    'sort_order'        => $setting->sort_order,
                ]);

                if ($index === 0 && count($snapshots) > 0) {
                    $this->initializeProcessSteps($process);
                }

                if ($firstProcess === null) {
                    $firstProcess = $process;
                }
            }

            return $firstProcess;
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
            'reject'  => $this->reject($processStepId),
            default   => abort(422, 'Invalid process step action.'),
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

        $step = ProcessStep::query()
            ->where('process_id', $process->id)
            ->where('assigned_user_id', $actorId)
            ->where('status', ProcessStepStatus::Pending)
            ->first();

        if ($step === null) {
            abort(422, 'No pending process step assigned to you for this client request.');
        }

        match ($action) {
            'approve' => $this->approve((string) $step->id),
            'reject'  => $this->reject((string) $step->id),
            default   => abort(422, 'Invalid process step action.'),
        };
    }

    public function approve(string $processStepId): ProcessStep
    {
        return DB::transaction(function () use ($processStepId) {
            /** @var ProcessStep $step */
            $step = ProcessStep::query()->whereKey($processStepId)->lockForUpdate()->firstOrFail();
            /** @var Process $process */
            $process = Process::query()->whereKey($step->process_id)->lockForUpdate()->firstOrFail();

            $this->assertActorCanActOnStep($step);
            $this->assertStepIsPending($step);

            if ($process->status === ProcessStatus::Pending) {
                $process->update(['status' => ProcessStatus::InProgress]);
            }

            $actorId = (string) Auth::id();
            $step->update([
                'status'    => ProcessStepStatus::Approved,
                'action_by' => $actorId,
                'acted_at'  => now(),
            ]);

            if ($process->execute_type === 'sequence') {
                $snapshot = $process->template_snapshot ?? [];
                $approvedCount = $process->steps()->where('status', ProcessStepStatus::Approved)->count();
                if ($approvedCount < count($snapshot)) {
                    $nextRow = $snapshot[$approvedCount];
                    $this->createProcessStepFromSnapshot($process, $nextRow);
                } else {
                    $process->update(['status' => ProcessStatus::Completed]);
                    $this->moveToNextProcessOrFinalize($process);
                }
            } else {
                $total = $process->steps()->count();
                $approved = $process->steps()->where('status', ProcessStepStatus::Approved)->count();
                if ($approved === $total && $total > 0) {
                    $process->update(['status' => ProcessStatus::Completed]);
                    $this->moveToNextProcessOrFinalize($process);
                }
            }

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

            $this->assertActorCanActOnStep($step);
            $this->assertStepIsPending($step);

            $actorId = (string) Auth::id();
            $step->update([
                'status'    => ProcessStepStatus::Rejected,
                'action_by' => $actorId,
                'acted_at'  => now(),
            ]);

            $process->update(['status' => ProcessStatus::Failed]);

            return $step->fresh();
        });
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
            $query = ProcessStep::query()
                ->where('process_id', $process->id)
                ->where('status', ProcessStepStatus::Pending)
                ->where('assigned_user_id', $actorId);

            $affected = $query->update([
                'status'    => ProcessStepStatus::Approved,
                'action_by' => $actorId,
                'acted_at'  => $now,
            ]);

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
                    'status'    => ProcessStepStatus::Rejected,
                    'action_by' => $actorId,
                    'acted_at'  => now(),
                ]);

            Process::query()
                ->where('processable_id', $cr->id)
                ->where('processable_type', self::TYPE_CLIENT_REQUEST)
                ->where('status', '!=', ProcessStatus::Completed)
                ->update(['status' => ProcessStatus::Failed]);
        });
    }

    private function resolveAssignedUserId(ProcedureSettingStep $step): ?string
    {
        if (is_string($step->user_id) && $step->user_id !== '') {
            return $step->user_id;
        }

        $firstTaker = $step->actionTakers->first();

        return $firstTaker !== null ? (string) $firstTaker->user_id : null;
    }

    /**
     * @param array{step_id: int, template_step_order: ?int, assigned_user_id: string, escalation_management_hierarchy_id: ?int} $row
     */
    private function createProcessStepFromSnapshot(Process $process, array $row): ProcessStep
    {
        return ProcessStep::query()->create([
            'process_id'                         => $process->id,
            'step_id'                            => $row['step_id'],
            'template_step_order'                => $row['template_step_order'],
            'assigned_user_id'                   => $row['assigned_user_id'],
            'escalation_management_hierarchy_id' => $row['escalation_management_hierarchy_id'],
            'status'                             => ProcessStepStatus::Pending,
        ]);
    }

    private function assertActorCanActOnStep(ProcessStep $step): void
    {
        if (! Auth::check()) {
            abort(403);
        }

        if ((string) Auth::id() !== (string) $step->assigned_user_id) {
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

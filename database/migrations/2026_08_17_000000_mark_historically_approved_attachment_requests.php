<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_CUTOFF = '2026-08-14 00:00:00';

    private const APPROVED_HISTORY_ACTIONS = [
        'attachment_approved',
        'workflow_step_approved',
        'request_approved',
    ];

    public function up(): void
    {
        if (! $this->hasRequiredSchema()) {
            return;
        }

        $lastRequestId = null;

        do {
            $query = DB::table('attachment_requests')
                ->where('created_at', '<', self::LEGACY_CUTOFF)
                ->select('id')
                ->orderBy('id')
                ->limit(200);

            if ($lastRequestId !== null) {
                $query->where('id', '>', $lastRequestId);
            }

            $requestIds = $query
                ->pluck('id')
                ->map(static fn ($id): string => (string) $id)
                ->all();

            foreach ($requestIds as $requestId) {
                if (! $this->lastHistoryEntryIsApproved($requestId)) {
                    continue;
                }

                DB::table('attachment_requests')
                    ->where('id', $requestId)
                    ->where('status', '!=', 'approved')
                    ->update(['status' => 'approved']);
            }

            $lastRequestId = $requestIds === [] ? $lastRequestId : end($requestIds);
        } while (count($requestIds) === 200);
    }

    public function down(): void
    {
        // The prior request status cannot be derived safely during rollback.
    }

    private function hasRequiredSchema(): bool
    {
        return Schema::hasTable('attachment_requests')
            && Schema::hasTable('attachment_request_history')
            && Schema::hasColumn('attachment_requests', 'created_at')
            && Schema::hasColumn('attachment_requests', 'status')
            && Schema::hasColumn('attachment_request_history', 'action')
            && Schema::hasColumn('attachment_request_history', 'metadata')
            && Schema::hasColumn('attachment_request_history', 'sort_order')
            && Schema::hasColumn('attachment_request_history', 'created_at');
    }

    private function lastHistoryEntryIsApproved(string $requestId): bool
    {
        $history = DB::table('attachment_request_history')
            ->where('attachment_request_id', $requestId)
            ->select(['action', 'metadata'])
            ->orderByRaw('sort_order is null desc')
            ->orderByDesc('sort_order')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if ($history === null) {
            return false;
        }

        if (in_array($history->action, self::APPROVED_HISTORY_ACTIONS, true)) {
            return true;
        }

        $metadata = $this->decodeJson($history->metadata);

        return ($metadata['status'] ?? null) === 'approved';
    }

    private function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
};

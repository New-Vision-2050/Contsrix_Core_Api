<?php

declare(strict_types=1);

namespace Modules\Forum\Services;

use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Modules\Forum\Enums\ForumReportStatus;
use Modules\Forum\Models\Forum;
use Modules\Forum\Models\ForumGroup;
use Modules\Forum\Models\ForumMessage;
use Modules\Forum\Models\ForumReport;
use Modules\User\Models\User;

class ForumReportService
{
    public function __construct(
        private ForumMessageService $messages,
        private ForumActivityLogger $activity,
    ) {}

    public function file(Forum $forum, ForumGroup $group, ForumMessage $message, User $reporter, string $reason): ForumReport
    {
        try {
            return ForumReport::query()->create([
                'forum_id' => $forum->id,
                'group_id' => $group->id,
                'message_id' => $message->id,
                'reporter_id' => $reporter->id,
                'reason' => $reason,
                'status' => ForumReportStatus::NEW->value,
            ]);
        } catch (QueryException $e) {
            $sqlState = $e->errorInfo[0] ?? '';
            if ($sqlState !== '23000' && $sqlState !== '23505') {
                throw $e;
            }

            throw ValidationException::withMessages([
                'message_id' => [__('forum::forum.already_reported')],
            ]);
        }
    }

    public function resolve(Forum $forum, ForumReport $report, User $actor, string $action, string $note): ForumReport
    {
        if ($action === 'delete_message' && $report->message !== null) {
            $this->messages->delete($report->message, $actor, true);
            $report->status = ForumReportStatus::CLOSED;
        } else {
            $report->status = ForumReportStatus::RESOLVED;
        }

        $report->resolution_note = $note;
        $report->resolved_by = $actor->id;
        $report->resolved_at = now();
        $report->save();

        $this->activity->log(
            (string) $forum->id,
            $actor,
            'report.resolved',
            'report',
            (string) $report->id,
            $report->reason,
            null,
            ['action' => $action, 'note' => $note],
        );

        return $report;
    }
}

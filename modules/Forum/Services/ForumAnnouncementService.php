<?php

declare(strict_types=1);

namespace Modules\Forum\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Forum\Events\ForumAnnouncementPublished;
use Modules\Forum\Models\Forum;
use Modules\Forum\Models\ForumAnnouncement;
use Modules\Forum\Presenters\ForumPresenter;
use Modules\User\Models\User;

class ForumAnnouncementService
{
    public function __construct(
        private ForumPushNotificationService $push,
        private ForumLocator $forums,
        private ForumActivityLogger $activity,
        private ForumAudience $audience,
    ) {}

    public function query(?string $forumId = null, ?string $status = null): Builder
    {
        return ForumAnnouncement::query()
            ->where('company_id', tenant('id'))
            ->when($forumId, fn (Builder $q) => $q->where('forum_id', $forumId))
            ->when($status, fn (Builder $q) => $q->where('status', $status));
    }

    public function find(string $id, ?string $forumId = null): ForumAnnouncement
    {
        return $this->query($forumId)->findOrFail($id);
    }

    /**
     * @return array{items: \Illuminate\Support\Collection<int, array<string, mixed>>, pagination: array<string, int>}
     */
    public function paginate(User $user, int $page, int $perPage, ?string $forumId = null, ?string $status = null, ?string $search = null): array
    {
        $paginator = $this->query($forumId, $status)
            ->with(['author', 'media'])
            ->when($search, fn (Builder $q) => $q->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')->orWhere('body', 'like', '%' . $search . '%');
            }))
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        $readIds = DB::table('forum_announcement_reads')
            ->where('user_id', $user->id)
            ->whereIn('announcement_id', $paginator->pluck('id'))
            ->pluck('announcement_id')
            ->all();

        return [
            'items' => $paginator->getCollection()->map(
                fn (ForumAnnouncement $a) => ForumPresenter::announcement($a, in_array($a->id, $readIds, true))
            ),
            'pagination' => self::pagination($paginator),
        ];
    }

    public function unreadCount(User $user, ?string $forumId = null): int
    {
        return $this->query($forumId, 'published')
            ->whereNotExists(function ($q) use ($user) {
                $q->select(DB::raw(1))
                    ->from('forum_announcement_reads as r')
                    ->whereColumn('r.announcement_id', 'forum_announcements.id')
                    ->where('r.user_id', $user->id);
            })
            ->count();
    }

    /**
     * @param array<int, UploadedFile> $attachments
     */
    public function publish(
        User $author,
        ?string $title,
        string $body,
        array $attachments = [],
        ?string $forumId = null,
        string $status = 'published',
        bool $notify = true,
    ): ForumAnnouncement {
        $forumId ??= $this->forums->defaultForum()->id;

        $announcement = ForumAnnouncement::query()->create([
            'forum_id' => $forumId,
            'title' => $title,
            'body' => $body,
            'status' => $status,
            'notify' => $notify,
            'created_by' => $author->id,
        ]);

        foreach ($attachments as $file) {
            $announcement->addMedia($file)->toMediaCollection(ForumAnnouncement::ATTACHMENT_COLLECTION);
        }

        $announcement->setRelation('author', $author);
        $announcement->load('media');
        $this->afterSave($announcement, $author);

        return $announcement;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, UploadedFile> $attachments
     */
    public function update(ForumAnnouncement $announcement, User $author, array $data, array $attachments = []): ForumAnnouncement
    {
        $announcement->update($data);

        foreach ($attachments as $file) {
            $announcement->addMedia($file)->toMediaCollection(ForumAnnouncement::ATTACHMENT_COLLECTION);
        }

        $announcement->load(['author', 'media']);
        $this->afterSave($announcement, $author);

        return $announcement;
    }

    private function afterSave(ForumAnnouncement $announcement, User $author): void
    {
        if ($announcement->status !== 'published') {
            return;
        }

        $forum = Forum::query()->find($announcement->forum_id);
        $payload = ForumPresenter::announcement($announcement, false);

        event(new ForumAnnouncementPublished(
            (string) $announcement->company_id,
            $payload,
            (string) $announcement->forum_id,
        ));

        $this->activity->log(
            (string) $announcement->forum_id,
            $author,
            'announcement.published',
            'announcement',
            (string) $announcement->id,
            $announcement->title,
        );

        if ($announcement->notify && $forum !== null) {
            $this->push->notifyAnnouncement(
                $announcement,
                $this->audience->ids($forum, (string) $author->id),
            );
        }
    }

    public function delete(ForumAnnouncement $announcement, ?User $actor = null): void
    {
        $this->activity->log(
            (string) $announcement->forum_id,
            $actor,
            'announcement.deleted',
            'announcement',
            (string) $announcement->id,
            $announcement->title,
        );
        $announcement->clearMediaCollection(ForumAnnouncement::ATTACHMENT_COLLECTION);
        $announcement->delete();
    }

    /**
     * @param array<int, string>|null $ids null marks every announcement as read
     */
    public function markRead(User $user, ?array $ids = null, ?string $forumId = null): void
    {
        $query = $this->query($forumId, 'published');
        if ($ids !== null) {
            $query->whereIn('id', $ids);
        }

        $rows = $query->pluck('id')->map(fn ($id) => [
            'announcement_id' => $id,
            'user_id' => $user->id,
            'read_at' => now(),
        ])->all();

        if ($rows !== []) {
            DB::table('forum_announcement_reads')->insertOrIgnore($rows);
        }
    }

    /**
     * @return array{total: int, published: int, drafts: int}
     */
    public function stats(?string $forumId): array
    {
        $base = $this->query($forumId);

        return [
            'total' => (clone $base)->count(),
            'published' => (clone $base)->where('status', 'published')->count(),
            'drafts' => (clone $base)->where('status', 'draft')->count(),
        ];
    }

    /**
     * @param \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, mixed> $paginator
     * @return array{page: int, next_page: int|null, last_page: int, result_count: int}
     */
    public static function pagination($paginator): array
    {
        return [
            'page' => $paginator->currentPage(),
            'next_page' => $paginator->currentPage() < $paginator->lastPage() ? $paginator->currentPage() + 1 : null,
            'last_page' => $paginator->lastPage(),
            'result_count' => $paginator->total(),
        ];
    }
}

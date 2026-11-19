<?php

declare(strict_types=1);

namespace Modules\Forum\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Forum\Events\ForumAnnouncementPublished;
use Modules\Forum\Models\ForumAnnouncement;
use Modules\Forum\Presenters\ForumPresenter;
use Modules\User\Models\User;

class ForumAnnouncementService
{
    public function __construct(
        private ForumPushNotificationService $push,
    ) {}

    public function query(): Builder
    {
        return ForumAnnouncement::query()->where('company_id', tenant('id'));
    }

    public function find(string $id): ForumAnnouncement
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * @return array{items: \Illuminate\Support\Collection<int, array<string, mixed>>, pagination: array<string, int>}
     */
    public function paginate(User $user, int $page, int $perPage): array
    {
        $paginator = $this->query()
            ->with(['author', 'media'])
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
            'pagination' => [
                'page' => $paginator->currentPage(),
                'next_page' => min($paginator->currentPage() + 1, $paginator->lastPage()),
                'last_page' => $paginator->lastPage(),
                'result_count' => $paginator->total(),
            ],
        ];
    }

    public function unreadCount(User $user): int
    {
        return $this->query()
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
    public function publish(User $author, ?string $title, string $body, array $attachments = []): ForumAnnouncement
    {
        $announcement = ForumAnnouncement::query()->create([
            'title' => $title,
            'body' => $body,
            'created_by' => $author->id,
        ]);

        foreach ($attachments as $file) {
            $announcement->addMedia($file)->toMediaCollection(ForumAnnouncement::ATTACHMENT_COLLECTION);
        }

        $announcement->setRelation('author', $author);
        $announcement->load('media');

        $companyId = (string) $announcement->company_id;

        event(new ForumAnnouncementPublished($companyId, ForumPresenter::announcement($announcement, false)));

        $recipientIds = User::query()
            ->where('company_id', $companyId)
            ->where('id', '!=', $author->id)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $this->push->notifyAnnouncement($announcement, $recipientIds);

        return $announcement;
    }

    public function delete(ForumAnnouncement $announcement): void
    {
        $announcement->clearMediaCollection(ForumAnnouncement::ATTACHMENT_COLLECTION);
        $announcement->delete();
    }

    /**
     * @param array<int, string>|null $ids null marks every announcement as read
     */
    public function markRead(User $user, ?array $ids = null): void
    {
        $query = $this->query();
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
}

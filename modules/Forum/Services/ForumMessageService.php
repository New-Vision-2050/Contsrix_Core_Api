<?php

declare(strict_types=1);

namespace Modules\Forum\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Modules\Forum\Enums\ForumMessageType;
use Modules\Forum\Events\ForumGroupActivity;
use Modules\Forum\Events\ForumMessageCreated;
use Modules\Forum\Events\ForumMessageDeleted;
use Modules\Forum\Models\ForumGroup;
use Modules\Forum\Models\ForumGroupMember;
use Modules\Forum\Models\ForumMessage;
use Modules\Forum\Presenters\ForumPresenter;
use Modules\User\Models\User;

class ForumMessageService
{
    public function __construct(
        private ForumAccessService $access,
        private ForumPushNotificationService $push,
    ) {}

    /**
     * @return array{messages: Collection<int, ForumMessage>, has_more: bool}
     */
    public function list(ForumGroup $group, ?string $beforeId, int $limit): array
    {
        $query = ForumMessage::query()
            ->where('group_id', $group->id)
            ->with(['sender', 'media', 'group:id,forum_id'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($beforeId !== null) {
            $before = ForumMessage::query()->where('group_id', $group->id)->find($beforeId);
            if ($before !== null) {
                $query->where(function ($q) use ($before) {
                    $q->where('created_at', '<', $before->created_at)
                        ->orWhere(function ($q) use ($before) {
                            $q->where('created_at', $before->created_at)->where('id', '<', $before->id);
                        });
                });
            }
        }

        $messages = $query->limit($limit + 1)->get();
        $hasMore = $messages->count() > $limit;

        return [
            'messages' => $messages->take($limit)->reverse()->values(),
            'has_more' => $hasMore,
        ];
    }

    public function send(
        ForumGroup $group,
        User $user,
        ForumMessageType $type,
        ?string $body,
        ?UploadedFile $attachment,
        ?int $durationSeconds,
    ): ForumMessage {
        $member = $this->access->membership($group, $user);

        abort_unless($this->access->canSend($group, $user, $member), 403, __('forum::forum.cannot_send'));
        abort_unless(
            in_array($type->value, $this->access->allowedTypes($group, $user, $member), true),
            403,
            __('forum::forum.type_not_allowed'),
        );

        $message = ForumMessage::query()->create([
            'group_id' => $group->id,
            'sender_id' => $user->id,
            'type' => $type->value,
            'body' => $body,
            'duration_seconds' => $type === ForumMessageType::VOICE ? $durationSeconds : null,
        ]);

        if ($attachment !== null) {
            $message->addMedia($attachment)->toMediaCollection(ForumMessage::ATTACHMENT_COLLECTION);
        }

        $message->setRelation('sender', $user);
        $message->setRelation('group', $group);
        $message->load('media');

        if ($member !== null) {
            $member->update(['last_read_at' => now()]);
        }

        $this->publish($group, $message, $user);

        return $message;
    }

    public function delete(ForumMessage $message, User $user, bool $asModerator = false): void
    {
        $group = $message->relationLoaded('group') ? $message->group : $message->group()->first();
        $allowed = $asModerator
            || (string) $message->sender_id === (string) $user->id
            || $this->access->isForumAdmin($user)
            || ($group !== null && $this->access->isForumManager($user, (string) ($group->forum_id ?? '')));

        abort_unless($allowed, 403, __('forum::forum.only_admins'));

        $groupId = (string) $message->group_id;
        $messageId = (string) $message->id;
        $forumId = $group?->forum_id;

        $message->clearMediaCollection(ForumMessage::ATTACHMENT_COLLECTION);
        $message->delete();

        event(new ForumMessageDeleted($groupId, $messageId, $forumId !== null ? (string) $forumId : null));
    }

    private function publish(ForumGroup $group, ForumMessage $message, User $sender): void
    {
        event(new ForumMessageCreated($group->id, ForumPresenter::message($message), (string) $group->forum_id));

        $recipientIds = ForumGroupMember::query()
            ->where('group_id', $group->id)
            ->where('user_id', '!=', $sender->id)
            ->pluck('user_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if ($recipientIds === []) {
            return;
        }

        event(new ForumGroupActivity($recipientIds, $group->id, ForumPresenter::lastMessage($message), (string) $group->forum_id));

        $this->push->notifyMessage($group, $message, $recipientIds);
    }
}

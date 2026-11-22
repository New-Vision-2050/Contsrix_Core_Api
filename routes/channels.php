<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Employee Task inbox notifications - one channel per action taker
Broadcast::channel('inbox.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});
Broadcast::channel('employee-task.inbox-counts.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});
Broadcast::channel('employee-task.notification.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});

// Safety task assignment — one private channel per assigned user
Broadcast::channel('safety.notification.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});

// Forum — private channels authorized through POST api/v1/forum/broadcasting/auth
Broadcast::channel('forum.group.{groupId}', function ($user, $groupId) {
    $group = \Modules\Forum\Models\ForumGroup::query()
        ->where('company_id', $user->company_id)
        ->find($groupId);

    if ($group === null) {
        return false;
    }

    $access = app(\Modules\Forum\Services\ForumAccessService::class);

    return $access->canView($group, $user, $access->membership($group, $user));
}, ['guards' => ['api']]);
Broadcast::channel('forum.user.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
}, ['guards' => ['api']]);
Broadcast::channel('forum.announcements.{id}', function ($user, $id) {
    if ((string) $user->company_id === (string) $id) {
        return true;
    }

    $forum = \Modules\Forum\Models\Forum::query()->find($id);
    if ($forum === null) {
        return false;
    }

    $access = app(\Modules\Forum\Services\ForumAccessService::class);

    return $access->canSeeForum($user, $forum) || $access->isForumManager($user, (string) $forum->id);
}, ['guards' => ['api']]);

//// Allow users to listen to their company's channel
//Broadcast::channel('company.{companyId}', function ($user, $companyId) {
//    return (string) $user->company_id === (string) $companyId;
//});

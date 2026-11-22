<?php

return [
    'permissions' => [
        // Human Resources sidebar → Forums.
        // human-resources.{area}*{resource}.{action}
        'FORUM_LIST' => 'human-resources.forums*forum.list',
        'FORUM_VIEW' => 'human-resources.forums*forum.view',
        'FORUM_CREATE' => 'human-resources.forums*forum.create',
        'FORUM_UPDATE' => 'human-resources.forums*forum.update',
        'FORUM_ARCHIVE' => 'human-resources.forums*forum.archive',
        'FORUM_RESTORE' => 'human-resources.forums*forum.restore',

        'FORUM_GROUP_LIST' => 'human-resources.forums*forum-group.list',
        'FORUM_GROUP_VIEW' => 'human-resources.forums*forum-group.view',
        'FORUM_GROUP_CREATE' => 'human-resources.forums*forum-group.create',
        'FORUM_GROUP_UPDATE' => 'human-resources.forums*forum-group.update',
        'FORUM_GROUP_DELETE' => 'human-resources.forums*forum-group.delete',
        'FORUM_GROUP_ARCHIVE' => 'human-resources.forums*forum-group.archive',
        'FORUM_GROUP_RESTORE' => 'human-resources.forums*forum-group.restore',

        'FORUM_MEMBER_LIST' => 'human-resources.forums*forum-member.list',
        'FORUM_MEMBER_UPDATE' => 'human-resources.forums*forum-member.update',

        'FORUM_ADMIN_LIST' => 'human-resources.forums*forum-admin.list',
        'FORUM_ADMIN_UPDATE' => 'human-resources.forums*forum-admin.update',

        'FORUM_ANNOUNCEMENT_LIST' => 'human-resources.forums*forum-announcement.list',
        'FORUM_ANNOUNCEMENT_CREATE' => 'human-resources.forums*forum-announcement.create',
        'FORUM_ANNOUNCEMENT_UPDATE' => 'human-resources.forums*forum-announcement.update',
        'FORUM_ANNOUNCEMENT_DELETE' => 'human-resources.forums*forum-announcement.delete',

        'FORUM_REPORT_LIST' => 'human-resources.forums*forum-report.list',
        'FORUM_REPORT_UPDATE' => 'human-resources.forums*forum-report.update',

        'FORUM_ACTIVITY_LIST' => 'human-resources.forums*forum-activity.list',
    ],
];

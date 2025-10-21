<?php

return [
    'permissions' => [
        // ================================================================================================
        // ARCHIVE LIBRARY MODULE PERMISSIONS
        // Document Management - Files & Folders
        // ================================================================================================

        // ============================================================
        // File Management Permissions
        // ============================================================
        'FILE_CREATE' => 'archive-library.archive-library*file.create',
        'FILE_UPDATE' => 'archive-library.archive-library*file.update',
        'FILE_DELETE' => 'archive-library.archive-library*file.delete',
        'FILE_EXPORT' => 'archive-library.archive-library*file.export',
        'FILE_ACTIVATE' => 'archive-library.archive-library*file.activate',

        // File Operations
//        'FILE_COPY' => 'archive-library.file.copy',
//        'FILE_CUT' => 'archive-library.file.cut',
//        'FILE_SHARE' => 'archive-library.file.share',
//        'FILE_CHANGE_STATUS' => 'archive-library.file.change-status',

        // ============================================================
        // Folder Management Permissions
        // ============================================================
        'FOLDER_LIST' => 'archive-library.archive-library*folder.list',
        'FOLDER_CREATE' => 'archive-library.archive-library*folder.create',
        'FOLDER_UPDATE' => 'archive-library.archive-library*folder.update',
        'FOLDER_DELETE' => 'archive-library.archive-library*folder.delete',
        'FOLDER_ACTIVATE' => 'archive-library.archive-library*folder.activate',
    ]
];

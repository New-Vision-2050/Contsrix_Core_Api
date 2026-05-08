<?php

return [
    'permissions' => [
        // ================================================================================================
        // REPORTS MODULE PERMISSIONS
        // Wizard-driven attendance/HR reports + reusable templates
        // ================================================================================================

        // Generated Reports
        'REPORT_LIST'     => 'human-resources.reports*reports.list',
        'REPORT_VIEW'     => 'human-resources.reports*reports.view',
        'REPORT_CREATE'   => 'human-resources.reports*reports.create',
        'REPORT_DELETE'   => 'human-resources.reports*reports.delete',
        'REPORT_DOWNLOAD' => 'human-resources.reports*reports.download',
        'REPORT_REGENERATE' => 'human-resources.reports*reports.regenerate',

        // Report Templates
        'REPORT_TEMPLATE_LIST'   => 'human-resources.reports*report-templates.list',
        'REPORT_TEMPLATE_VIEW'   => 'human-resources.reports*report-templates.view',
        'REPORT_TEMPLATE_CREATE' => 'human-resources.reports*report-templates.create',
        'REPORT_TEMPLATE_UPDATE' => 'human-resources.reports*report-templates.update',
        'REPORT_TEMPLATE_DELETE' => 'human-resources.reports*report-templates.delete',
    ],
];

<?php
return
[
    'permissions' => [
                                        
        'EMPLOYEE_ATTENDANCE_VIEW'   => 'human-resources.attendance*attendance-list.view',
        'EMPLOYEE_ATTENDANCE_CREATE' => 'human-resources.attendance*attendance-list.create',
        'EMPLOYEE_ATTENDANCE_UPDATE' => 'human-resources.attendance*attendance-list.update',
        'EMPLOYEE_ATTENDANCE_DELETE' => 'human-resources.attendance*attendance-list.delete',
        'EMPLOYEE_ATTENDANCE_EXPORT' => 'human-resources.attendance*attendance-list.export',
        'EMPLOYEE_ATTENDANCE_MAP'    => 'human-resources.attendance*attendance-map.view',

        'EMPLOYEE_ATTENDANCE_CONSTRAINTS_VIEW' => 'human-resources.attendance*attendance-constraints.view',
        'EMPLOYEE_ATTENDANCE_CONSTRAINTS_CREATE' => 'human-resources.attendance*attendance-constraints.create',
        'EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE' => 'human-resources.attendance*attendance-constraints.update',
        'EMPLOYEE_ATTENDANCE_CONSTRAINTS_DELETE' => 'human-resources.attendance*attendance-constraints.delete',

        'ATTENDANCE_REPORTS_VIEW' => 'human-resources.attendance*attendance-reports.view',
    ],
];
 
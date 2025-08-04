<?php
return
[
    'permissions' => [

        'EMPLOYEE_ATTENDANCE_VIEW'   => 'human-resources.attendance-list*attendance-list.view',
        'EMPLOYEE_ATTENDANCE_CREATE' => 'human-resources.attendance-list*attendance-list.create',
        'EMPLOYEE_ATTENDANCE_UPDATE' => 'human-resources.attendance-list*attendance-list.update',
        'EMPLOYEE_ATTENDANCE_DELETE' => 'human-resources.attendance-list*attendance-list.delete',
        'EMPLOYEE_ATTENDANCE_EXPORT' => 'human-resources.attendance-list*attendance-list.export',
        'EMPLOYEE_ATTENDANCE_MAP'    => 'human-resources.attendance-list*attendance-map.view',

        'EMPLOYEE_ATTENDANCE_CONSTRAINTS_VIEW' => 'human-resources.attendance-constraints*attendance-constraints.view',
        'EMPLOYEE_ATTENDANCE_CONSTRAINTS_CREATE' => 'human-resources.attendance-constraints*attendance-constraints.create',
        'EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE' => 'human-resources.attendance-constraints*attendance-constraints.update',
        'EMPLOYEE_ATTENDANCE_CONSTRAINTS_DELETE' => 'human-resources.attendance-constraints*attendance-constraints.delete',
    ]
];

<?php

return [
    'permissions' => [
        // ================================================================================================
        // ADVANCE MODULE PERMISSIONS (السلف)
        // Human Resource Settings - Advance Policy Management + Advance Requests
        // ================================================================================================

        // Advance Policy Management (settings form — no types)
        'ADVANCE_POLICY_LIST' => 'human-resources.settings*advance-policy.list',
        'ADVANCE_POLICY_VIEW' => 'human-resources.settings*advance-policy.view',
        'ADVANCE_POLICY_CREATE' => 'human-resources.settings*advance-policy.create',
        'ADVANCE_POLICY_UPDATE' => 'human-resources.settings*advance-policy.update',
        'ADVANCE_POLICY_DELETE' => 'human-resources.settings*advance-policy.delete',
        'ADVANCE_POLICY_ASSIGN' => 'human-resources.settings*advance-policy.assign',

        // Advance Request Management (admin list/view; create/approve/reject are
        // self-service + workflow-authorization gated, not permission gated)
        'ADVANCE_REQUEST_LIST' => 'human-resources.advance-requests*advance-request.list',
        'ADVANCE_REQUEST_VIEW' => 'human-resources.advance-requests*advance-request.view',
    ]
];

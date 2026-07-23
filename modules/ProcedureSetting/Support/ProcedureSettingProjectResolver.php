<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ProcedureSettingProjectResolver
{
    public static function projectIdForProcedureSetting(mixed $procedureSettingId): ?string
    {
        if (! is_string($procedureSettingId) || $procedureSettingId === '') {
            return null;
        }

        $projectId = self::projectIdFromWorkFlow($procedureSettingId);
        if ($projectId !== null) {
            return $projectId;
        }

        return self::projectIdFromProjectProcedureSetting($procedureSettingId);
    }

    private static function projectIdFromWorkFlow(string $procedureSettingId): ?string
    {
        if (! Schema::hasTable('work_flows') || ! Schema::hasColumn('work_flows', 'project_id')) {
            return null;
        }

        $projectId = DB::table('procedure_settings')
            ->join('work_flows', 'work_flows.id', '=', 'procedure_settings.work_flow_id')
            ->where('procedure_settings.id', $procedureSettingId)
            ->value('work_flows.project_id');

        return is_string($projectId) && $projectId !== '' ? $projectId : null;
    }

    private static function projectIdFromProjectProcedureSetting(string $procedureSettingId): ?string
    {
        if (! Schema::hasTable('project_procedure_settings')) {
            return null;
        }

        $projectId = DB::table('project_procedure_settings')
            ->where('procedure_setting_id', $procedureSettingId)
            ->value('project_id');

        return is_string($projectId) && $projectId !== '' ? $projectId : null;
    }
}

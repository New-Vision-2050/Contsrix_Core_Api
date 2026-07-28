<?php

namespace Modules\Project\ProjectType\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;

class SafetyAnalyticsPresenter extends AbstractPresenter
{
    /**
     * @param  array<string, mixed>  $item
     */
    public function __construct(private array $item) {}

    protected function present(bool $isListing = false): array
    {
        return $this->item;
    }

    public static function overall(array $data): array
    {
        return (new self([
            'average_percentage' => isset($data['average_percentage'])
                ? round((float) $data['average_percentage'], 2)
                : 0.0,
            'total_evaluations' => (int) ($data['total_evaluations'] ?? 0),
            'completed_evaluations' => (int) ($data['completed_evaluations'] ?? 0),
            'pending_evaluations' => (int) ($data['pending_evaluations'] ?? 0),
        ]))->getData();
    }

    public static function compliant(array $data): array
    {
        return (new self([
            'project_id' => $data['project_id'] ?? null,
            'compliant_locations' => (int) ($data['compliant_locations'] ?? 0),
            'total_locations' => (int) ($data['total_locations'] ?? 0),
            'is_project_compliant' => (bool) ($data['is_project_compliant'] ?? false),
        ]))->getData();
    }

    public static function frequentViolation(array $item): array
    {
        return (new self([
            'id' => $item['id'] ?? null,
            'code' => $item['code'] ?? null,
            'description' => $item['description'] ?? null,
            'category' => $item['category'] ?? null,
            'default_weight' => $item['default_weight'] ?? null,
            'count' => (int) ($item['count'] ?? 0),
        ]))->getData();
    }

    public static function violationPerformance(array $item): array
    {
        return (new self([
            'id' => $item['id'] ?? null,
            'code' => $item['code'] ?? null,
            'description' => $item['description'] ?? null,
            'category' => $item['category'] ?? null,
            'total_evaluations' => (int) ($item['total_evaluations'] ?? 0),
            'violation_found_count' => (int) ($item['violation_found_count'] ?? 0),
            'no_violation_count' => (int) ($item['no_violation_count'] ?? 0),
            'not_applicable_count' => (int) ($item['not_applicable_count'] ?? 0),
            'compliance_rate' => isset($item['compliance_rate'])
                ? round((float) $item['compliance_rate'], 2)
                : 0.0,
        ]))->getData();
    }

    public static function byContractorConsultant(array $item): array
    {
        return (new self([
            'contractor_id' => $item['contractor_id'] ?? null,
            'contractor_name' => $item['contractor_name'] ?? null,
            'consultant' => $item['consultant'] ?? null,
            'consultant_engineer' => $item['consultant_engineer'] ?? null,
            'violation_count' => (int) ($item['violation_count'] ?? 0),
        ]))->getData();
    }

    public static function topViolation(array $item): array
    {
        return self::frequentViolation($item);
    }
}

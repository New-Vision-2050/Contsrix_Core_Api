<?php

declare(strict_types=1);

namespace Modules\Reports\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Reports\Enums\ReportEnums;
use Modules\Reports\Enums\ReportStatus;
use Modules\Reports\Models\Report;

/** @extends Factory<Report> */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'name' => [
                'ar' => 'تقرير اختبار',
                'en' => 'Test Report',
            ],
            'report_types'      => [ReportEnums::REPORT_TYPE_ATTENDANCE_ABSENCE],
            'period_type'       => 'monthly',
            'year'              => (int) date('Y'),
            'month'             => (int) date('n'),
            'export_format'     => 'pdf',
            'language'          => 'ar',
            'paper_size'        => 'A4',
            'print_orientation' => 'portrait',
            'config'            => [],
            'status'            => ReportStatus::PENDING,
        ];
    }
}

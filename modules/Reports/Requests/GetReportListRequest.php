<?php

declare(strict_types=1);

namespace Modules\Reports\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Reports\Enums\ReportEnums;
use Modules\Reports\Enums\ReportStatus;

class GetReportListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page'    => 'integer|min:1|max:200',
            'page'        => 'integer|min:1',
            'search'      => 'nullable|string|max:255',
            'status'      => ['nullable', 'string', 'in:' . implode(',', ReportStatus::all())],
            'report_type' => ['nullable', 'string', 'in:' . implode(',', ReportEnums::reportTypes())],
            'period_type' => ['nullable', 'string', 'in:' . implode(',', ReportEnums::periodTypes())],
            'year'        => 'nullable|integer|min:2000|max:2100',
            'month'       => 'nullable|integer|min:1|max:12',
            'date_from'   => 'nullable|date',
            'date_to'     => 'nullable|date|after_or_equal:date_from',
            'template_id' => 'nullable|uuid',
        ];
    }
}

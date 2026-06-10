<?php

declare(strict_types=1);

namespace Modules\Reports\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Reports\Enums\ReportEnums;

class CreateEmployeeReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'          => 'required|string|exists:users,global_id',
            'date_from'        => 'required|date|before_or_equal:date_to',
            'date_to'          => 'required|date|after_or_equal:date_from',
            'name'             => 'nullable|array',
            'name.ar'          => 'nullable|string|max:255',
            'name.en'          => 'nullable|string|max:255',
            'report_language'  => ['nullable', 'string', 'in:' . implode(',', ReportEnums::reportLanguages())],
            'paper_size'       => ['nullable', 'string', 'in:' . implode(',', ReportEnums::paperSizes())],
            'print_orientation'=> ['nullable', 'string', 'in:' . implode(',', ReportEnums::printOrientations())],
        ];
    }

    public function getUserId(): string
    {
        return (string) $this->input('user_id');
    }

    public function getDateFrom(): string
    {
        return (string) $this->input('date_from');
    }

    public function getDateTo(): string
    {
        return (string) $this->input('date_to');
    }

    public function getReportLanguage(): string
    {
        return (string) $this->input('report_language', ReportEnums::LANGUAGE_AR);
    }

    public function getPaperSize(): string
    {
        return (string) $this->input('paper_size', ReportEnums::PAPER_A4);
    }

    public function getPrintOrientation(): string
    {
        return (string) $this->input('print_orientation', ReportEnums::ORIENTATION_PORTRAIT);
    }

    public function getName(): ?array
    {
        $name = $this->input('name');
        return is_array($name) ? $name : null;
    }
}

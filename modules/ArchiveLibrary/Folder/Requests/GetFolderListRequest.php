<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetFolderListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page' => 'integer',
            'page' => 'integer',
            'document_type' => 'nullable|string|in:pdf,png,jpg,jpeg,doc,docx,xls,xlsx,txt,zip,rar,csv,fav',
            'end_date' => 'nullable|date',
            'end_date_from' => 'nullable|date',
            'end_date_to' => 'nullable|date|after_or_equal:end_date_from',
            'search' => 'nullable|string|max:255',
            'type' => 'nullable|string|in:all,name,reference_number,employee',
            'branch_id' => 'nullable|integer|exists:management_hierarchies,id',
            'sort' => 'nullable|string|in:asc,desc',
            'withoutTenancy' => 'nullable|boolean',
        ];
    }

    public function wantsWithoutTenancy(): bool
    {
        return $this->boolean('withoutTenancy');
    }

    public function getDocumentType(): ?string
    {
        return $this->input('document_type') =="fav" ? null : $this->input('document_type');
    }

    public function getEndDate(): ?string
    {
        return $this->input('end_date');
    }

    public function getEndDateFrom(): ?string
    {
        return $this->input('end_date_from');
    }

    public function getEndDateTo(): ?string
    {
        return $this->input('end_date_to');
    }

    public function getSearch(): ?string
    {
        return $this->input('search');
    }

    public function getSearchType(): string
    {
        return $this->input('type', 'all'); // Default to 'all' if not provided
    }

    public function getBranchId(): ?int
    {
        return $this->input('branch_id') ? (int) $this->input('branch_id') : null;
    }

    public function getSort(): ?string
    {
        return $this->input('sort');
    }

    public function getIsFavourite(): ?bool
    {
        return $this->input('document_type') == "fav" ? true : null;
    }
}

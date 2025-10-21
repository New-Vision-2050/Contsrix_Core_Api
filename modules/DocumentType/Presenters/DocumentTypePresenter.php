<?php

declare(strict_types=1);

namespace Modules\DocumentType\Presenters;

use Modules\DocumentType\Models\DocumentType;
use BasePackage\Shared\Presenters\AbstractPresenter;

class DocumentTypePresenter extends AbstractPresenter
{
    private DocumentType $documentType;

    public function __construct(DocumentType $documentType)
    {
        $this->documentType = $documentType;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->documentType->id,
            'name' => $this->documentType->name,
            'is_active' => $this->documentType->is_active,
            'company_id' => $this->documentType->company_id,
        ];
    }
}

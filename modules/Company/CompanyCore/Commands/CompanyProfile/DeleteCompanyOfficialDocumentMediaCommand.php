<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Commands\CompanyProfile;

use Ramsey\Uuid\UuidInterface;

class DeleteCompanyOfficialDocumentMediaCommand
{
    public function __construct(
        private UuidInterface $id,
        private               $fileId,


    )
    {

    }


    public function getId()
    {
        return $this->id;
    }

    public function getFileId()
    {
        return $this->fileId;
    }


}

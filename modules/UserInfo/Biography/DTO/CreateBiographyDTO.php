<?php

declare(strict_types=1);

namespace Modules\UserInfo\Biography\DTO;

use Ramsey\Uuid\UuidInterface;
use Illuminate\Http\UploadedFile;
class CreateBiographyDTO
{
    public function __construct(
        public UploadedFile  $file,
        public string $company_id,
        public string $global_id,

    ) {
    }

    public function toArray(): array
    {
        return [
            'file' => $this->file->getClientOriginalName(),
            'company_id' => $this->company_id,
            'global_id' => $this->global_id,
        ];
    }
}

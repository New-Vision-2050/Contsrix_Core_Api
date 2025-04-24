<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Presenters;

use Modules\ArchiveLibrary\File\Models\File;
use BasePackage\Shared\Presenters\AbstractPresenter;

class FilePresenter extends AbstractPresenter
{
    private File $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->file->id,
            'name' => $this->file->name,
            'media_urls' => $this->file?->media_urls
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Presenters;

use Modules\ArchiveLibrary\Folder\Models\Folder;
use BasePackage\Shared\Presenters\AbstractPresenter;

class FolderPresenter extends AbstractPresenter
{
    private Folder $folder;

    public function __construct(Folder $folder)
    {
        $this->folder = $folder;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->folder->id,
            'name' => $this->folder->name,
            'parent_id' => $this->folder?->parent_id,
            
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Presenters;

use Modules\ArchiveLibrary\Folder\Models\Folder;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\Media\Presenters\MediaPresenter;

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
            'access_type' => $this->folder->access_type,
            'file' => $this->folder->getFirstMedia("upload") ? (new MediaPresenter($this->folder->getFirstMedia('upload')))->getData(): null,
            "created_at"=>$this->folder->created_at,
            "updated_at"=>$this->folder->updated_at,
            "is_password"=>$this->folder->password != null?1 : 0
        ];
    }
}

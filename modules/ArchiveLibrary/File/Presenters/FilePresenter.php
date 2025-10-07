<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Presenters;

use Modules\ArchiveLibrary\File\Models\File;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\Media\Presenters\MediaPresenter;

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
            'reference_number' => $this->file->reference_number,
            'start_date' => $this->file->start_date?->format('Y-m-d'),
            'end_date' => $this->file->end_date?->format('Y-m-d'),
            'access_type' => $this->file->access_type,
            'file' => $this->file->media ? (new MediaPresenter($this->file->getFirstMedia('upload')))->getData(): null,
            'users' => $this->file->users ? $this->file->users->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ]) : [],
            "created_at"=>$this->file->created_at,
            "updated_at"=>$this->file->updated_at,
        ];
    }
}

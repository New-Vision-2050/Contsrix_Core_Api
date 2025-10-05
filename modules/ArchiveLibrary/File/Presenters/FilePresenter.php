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
            'reference_number' => $this->file->reference_number,
            'start_date' => $this->file->start_date?->format('Y-m-d'),
            'end_date' => $this->file->end_date?->format('Y-m-d'),
            'access_type' => $this->file->access_type,
            'media' => $this->file->media ? $this->file->media->map(fn($media) => [
                'id' => $media->id,
                'file_name' => $media->file_name,
                'url' => $media->getFullUrl(),
            ]) : [],
            'users' => $this->file->users ? $this->file->users->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ]) : [],
        ];
    }
}

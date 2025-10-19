<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\ArchiveLibrary\File\Models\File;

class FavouriteFilePresenter extends AbstractPresenter
{
    public function __construct(
        private File $file
    ) {
    }

    public function getData(): array
    {
        return [
            'id' => $this->file->id,
            'name' => $this->file->name,
            'reference_number' => $this->file->reference_number,
            'access_type' => $this->file->access_type,
            'status' => $this->file->status,
            'start_date' => $this->file->start_date?->format('Y-m-d'),
            'end_date' => $this->file->end_date?->format('Y-m-d'),
            'folder' => $this->file->folder ? [
                'id' => $this->file->folder->id,
                'name' => $this->file->folder->name
            ] : null,
            'added_to_favourites_at' => $this->file->pivot?->created_at?->toIso8601String(),
            'media_urls' => $this->file->media_urls ?? []
        ];
    }
}

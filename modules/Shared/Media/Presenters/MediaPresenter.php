<?php

namespace Modules\Shared\Media\Presenters;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaPresenter
{
    private Media $media;

    public function __construct(Media $media)
    {
        $this->media = $media;
    }

    public function getData(): array
    {
        return [
            'id' => $this->media->id,
            'url' => $this->media->getFullUrl(),
            'name' => $this->media->name,
            'mime_type' => $this->media->mime_type,
            'type' => $this->media->type,
        ];
    }

    public static function collection($mediaItems): array
    {
        return collect($mediaItems)->map(fn($media) => (new self($media))->getData())->toArray();
    }
}

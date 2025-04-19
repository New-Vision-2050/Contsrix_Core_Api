<?php

namespace Modules\Shared\Media\MediaLibrary;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class CustomPathGenerator extends DefaultPathGenerator
{
    public function getPath(Media $media): string
    {
        $customPath = $media->getCustomProperty('file_path') ?? 'default_path';
        return "{$customPath}/";
    }

    public function getPathForConversions(Media $media): string
    {
        // Store converted images inside the same custom path
        $customPath = $media->getCustomProperty('file_path') ?? 'default_path';
        return "{$customPath}/conversions/";
    }
    public function getPathForResponsiveImages(Media $media): string
    {
        $customPath = $media->getCustomProperty('file_path') ?? 'default_path';
        return "{$customPath}/responsive/";
    }
    public function getFullUrl(Media $media): string
    {
        $disk = $media->conversions_disk;
        if($disk=="s3_private")
        {
            $url = Storage::disk($disk)->temporaryUrl(
                $this->getPathRelativeToRoot($media),
                Carbon::now()->addMinutes(60)
            );
        }else
        {
            $url = Storage::disk($disk)->url(
                $this->getPathRelativeToRoot($media)

            );
        }



        return $url;
    }

    private function getPathRelativeToRoot(Media $media): string
    {
        return $this->getPath($media) . $media->file_name;
    }
}


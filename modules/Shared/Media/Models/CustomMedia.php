<?php

declare(strict_types=1);

namespace Modules\Shared\Media\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Modules\Shared\Media\MediaLibrary\CustomPathGenerator;

class CustomMedia extends Media
{
    public function getFullUrl(string $conversionName = ''): string
    {
        return (new CustomPathGenerator)->getFullUrl($this);
    }
        /**
     * Get the full URL for the media file (original).
     *
     * @param string $conversionName
     * @return string
     */
    public function original_url(string $conversionName = ''): string
    {
        // Use the custom path generator to get the full URL
        return (new CustomPathGenerator)->getFullUrl($this);
    }
}


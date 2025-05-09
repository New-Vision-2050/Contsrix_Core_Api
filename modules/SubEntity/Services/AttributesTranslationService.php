<?php

declare(strict_types=1);

namespace Modules\SubEntity\Services;

class AttributesTranslationService
{
    static function getTranslations(string $key): array
    {
        return [
            'id' => $key,
            'name' => [
                'en' => __($key, [], 'en'),
                'ar' => __($key, [], 'ar')
            ]
        ];
    }
}

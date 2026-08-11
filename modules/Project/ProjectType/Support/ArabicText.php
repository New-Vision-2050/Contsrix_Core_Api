<?php

namespace Modules\Project\ProjectType\Support;

/**
 * Minimal Arabic presentation-form reshaper for GD imagettftext.
 * Converts logical Arabic into visual glyphs so FreeType renders connected letters.
 */
class ArabicText
{
    /**
     * @var array<string, array{0:?string,1:?string,2:?string,3:?string}>
     */
    private const MAP = [
        'ء' => ['ء', 'ء', 'ء', 'ء'],
        'آ' => ['ﺁ', 'ﺂ', 'ﺂ', 'ﺁ'],
        'أ' => ['ﺃ', 'ﺄ', 'ﺄ', 'ﺃ'],
        'ؤ' => ['ﺅ', 'ﺆ', 'ﺆ', 'ﺅ'],
        'إ' => ['ﺇ', 'ﺈ', 'ﺈ', 'ﺇ'],
        'ئ' => ['ﺉ', 'ﺊ', 'ﺌ', 'ﺋ'],
        'ا' => ['ﺍ', 'ﺎ', 'ﺎ', 'ﺍ'],
        'ب' => ['ﺏ', 'ﺐ', 'ﺒ', 'ﺑ'],
        'ة' => ['ﺓ', 'ﺔ', 'ﺔ', 'ﺓ'],
        'ت' => ['ﺕ', 'ﺖ', 'ﺘ', 'ﺗ'],
        'ث' => ['ﺙ', 'ﺚ', 'ﺜ', 'ﺛ'],
        'ج' => ['ﺝ', 'ﺞ', 'ﺠ', 'ﺟ'],
        'ح' => ['ﺡ', 'ﺢ', 'ﺤ', 'ﺣ'],
        'خ' => ['ﺥ', 'ﺦ', 'ﺨ', 'ﺧ'],
        'د' => ['ﺩ', 'ﺪ', 'ﺪ', 'ﺩ'],
        'ذ' => ['ﺫ', 'ﺬ', 'ﺬ', 'ﺫ'],
        'ر' => ['ﺭ', 'ﺮ', 'ﺮ', 'ﺭ'],
        'ز' => ['ﺯ', 'ﺰ', 'ﺰ', 'ﺯ'],
        'س' => ['ﺱ', 'ﺲ', 'ﺴ', 'ﺳ'],
        'ش' => ['ﺵ', 'ﺶ', 'ﺸ', 'ﺷ'],
        'ص' => ['ﺹ', 'ﺺ', 'ﺼ', 'ﺻ'],
        'ض' => ['ﺽ', 'ﺾ', 'ﻀ', 'ﺿ'],
        'ط' => ['ﻁ', 'ﻂ', 'ﻄ', 'ﻃ'],
        'ظ' => ['ﻅ', 'ﻆ', 'ﻈ', 'ﻇ'],
        'ع' => ['ﻉ', 'ﻊ', 'ﻌ', 'ﻋ'],
        'غ' => ['ﻍ', 'ﻎ', 'ﻐ', 'ﻏ'],
        'ف' => ['ﻑ', 'ﻒ', 'ﻔ', 'ﻓ'],
        'ق' => ['ﻕ', 'ﻖ', 'ﻘ', 'ﻗ'],
        'ك' => ['ﻙ', 'ﻚ', 'ﻜ', 'ﻛ'],
        'ل' => ['ﻝ', 'ﻞ', 'ﻠ', 'ﻟ'],
        'م' => ['ﻡ', 'ﻢ', 'ﻤ', 'ﻣ'],
        'ن' => ['ﻥ', 'ﻦ', 'ﻨ', 'ﻧ'],
        'ه' => ['ﻩ', 'ﻪ', 'ﻬ', 'ﻫ'],
        'و' => ['ﻭ', 'ﻮ', 'ﻮ', 'ﻭ'],
        'ى' => ['ﻯ', 'ﻰ', 'ﻰ', 'ﻯ'],
        'ي' => ['ﻱ', 'ﻲ', 'ﻴ', 'ﻳ'],
        'لآ' => ['ﻵ', 'ﻶ', 'ﻶ', 'ﻵ'],
        'لأ' => ['ﻷ', 'ﻸ', 'ﻸ', 'ﻷ'],
        'لإ' => ['ﻹ', 'ﻺ', 'ﻺ', 'ﻹ'],
        'لا' => ['ﻻ', 'ﻼ', 'ﻼ', 'ﻻ'],
    ];

    private const NON_CONNECTING = 'آأؤإادذرزو';

    public static function forGd(string $text): string
    {
        if ($text === '' || ! preg_match('/\p{Arabic}/u', $text)) {
            return $text;
        }

        if (class_exists(\ArPHP\I18N\Arabic::class)) {
            try {
                /** @var \ArPHP\I18N\Arabic $arabic */
                $arabic = new \ArPHP\I18N\Arabic();

                return $arabic->utf8Glyphs($text);
            } catch (\Throwable) {
                // fall through to local reshaper
            }
        }

        return self::reshape($text);
    }

    private static function reshape(string $text): string
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = [];
        $count = count($chars);

        for ($i = 0; $i < $count; $i++) {
            $char = $chars[$i];

            // Lam-alef ligatures
            if ($char === 'ل' && isset($chars[$i + 1]) && isset(self::MAP['ل'.$chars[$i + 1]])) {
                $lig = 'ل'.$chars[$i + 1];
                $prev = $i > 0 ? $chars[$i - 1] : '';
                $connectPrev = $prev !== '' && self::connectsToNext($prev);
                $form = $connectPrev ? 1 : 0;
                $out[] = self::MAP[$lig][$form] ?? $lig;
                $i++;

                continue;
            }

            if (! isset(self::MAP[$char])) {
                $out[] = $char;

                continue;
            }

            $prev = $i > 0 ? $chars[$i - 1] : '';
            $next = $i < $count - 1 ? $chars[$i + 1] : '';
            $connectPrev = $prev !== '' && self::connectsToNext($prev);
            $connectNext = $next !== '' && isset(self::MAP[$next]) && ! self::isNonConnecting($char);

            if ($connectPrev && $connectNext) {
                $form = 2; // middle
            } elseif ($connectPrev) {
                $form = 1; // final
            } elseif ($connectNext) {
                $form = 3; // initial
            } else {
                $form = 0; // isolated
            }

            $out[] = self::MAP[$char][$form] ?? $char;
        }

        // Visual order for RTL in GD (left-to-right drawing)
        return implode('', array_reverse($out));
    }

    private static function connectsToNext(string $char): bool
    {
        return isset(self::MAP[$char]) && ! self::isNonConnecting($char);
    }

    private static function isNonConnecting(string $char): bool
    {
        return mb_strpos(self::NON_CONNECTING, $char) !== false;
    }
}

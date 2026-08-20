<?php

namespace Modules\Project\ProjectType\Support;

/**
 * Central Arabic-capable font resolver for Safety PDF generation.
 *
 * Works the same on Windows (local) and Linux (server) by preferring
 * project-bundled TTFs, then mPDF vendor fonts, then system fonts.
 */
class SafetyPdfFonts
{
    /** Logical mPDF font family name used in HTML/CSS. */
    public const FAMILY = 'safety';

    /**
     * Absolute directories that contain TTF files for mPDF.
     *
     * @return list<string>
     */
    public static function fontDirectories(): array
    {
        $dirs = [];

        $projectFonts = base_path('modules/Project/ProjectType/Resources/fonts');
        if (is_dir($projectFonts)) {
            $dirs[] = $projectFonts;
        }

        $mpdfFonts = base_path('vendor/mpdf/mpdf/ttfonts');
        if (is_dir($mpdfFonts)) {
            $dirs[] = $mpdfFonts;
        }

        foreach ([
            '/usr/share/fonts/truetype/dejavu',
            '/usr/share/fonts/truetype/liberation',
            '/usr/share/fonts/TTF',
            'C:/Windows/Fonts',
        ] as $systemDir) {
            if (is_dir($systemDir)) {
                $dirs[] = $systemDir;
            }
        }

        return array_values(array_unique($dirs));
    }

    /**
     * Resolve a concrete TTF path for GD chart rendering.
     */
    public static function ttfPath(bool $bold = false): string
    {
        $candidates = $bold
            ? [
                base_path('modules/Project/ProjectType/Resources/fonts/DejaVuSans-Bold.ttf'),
                base_path('vendor/mpdf/mpdf/ttfonts/DejaVuSans-Bold.ttf'),
                '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
                'C:/Windows/Fonts/arialbd.ttf',
                'C:/Windows/Fonts/tahomabd.ttf',
                'C:/Windows/Fonts/segoeuib.ttf',
            ]
            : [
                base_path('modules/Project/ProjectType/Resources/fonts/DejaVuSans.ttf'),
                base_path('vendor/mpdf/mpdf/ttfonts/DejaVuSans.ttf'),
                '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
                'C:/Windows/Fonts/arial.ttf',
                'C:/Windows/Fonts/tahoma.ttf',
                'C:/Windows/Fonts/segoeui.ttf',
            ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return '';
    }

    /**
     * Build mPDF fontDir + fontdata + default_font for Arabic RTL reports.
     *
     * @return array{fontDir: list<string>, fontdata: array<string, mixed>, default_font: string}
     */
    public static function mpdfConfig(): array
    {
        $defaults = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $fontDirs = array_values(array_unique(array_merge(
            $defaults['fontDir'] ?? [],
            self::fontDirectories()
        )));

        $fontData = (new \Mpdf\Config\FontVariables())->getDefaults()['fontdata'];

        // Primary family: project-bundled DejaVu (identical local + server).
        $regular = self::ttfPath(false);
        $bold = self::ttfPath(true);

        if ($regular !== '') {
            $fontData[self::FAMILY] = [
                'R' => basename($regular),
                'B' => basename($bold !== '' ? $bold : $regular),
                'useOTL' => 0xFF,
                'useKashida' => 75,
            ];

            // Alias so existing HTML "arial" / "dejavusans" still render correctly.
            $fontData['arial'] = $fontData[self::FAMILY];
            $fontData['dejavusans'] = array_merge(
                $fontData['dejavusans'] ?? [],
                [
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ]
            );
        }

        return [
            'fontDir' => $fontDirs,
            'fontdata' => $fontData,
            'default_font' => self::FAMILY,
        ];
    }

    /** CSS font-family value for WriteHTML / Blade snippets. */
    public static function cssFamily(): string
    {
        return self::FAMILY.', dejavusans, sans-serif';
    }
}

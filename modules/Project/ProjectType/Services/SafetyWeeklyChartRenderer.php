<?php

namespace Modules\Project\ProjectType\Services;

use Modules\Project\ProjectType\Support\ArabicText;

/**
 * Renders chart PNGs that visually match the weekly safety PDF template.
 */
class SafetyWeeklyChartRenderer
{
    /** @var list<array{0:int,1:int,2:int}> */
    private const PIE_COLORS = [
        [46, 117, 182],   // blue
        [217, 65, 53],    // red
        [242, 180, 36],   // yellow
        [76, 175, 80],    // green
        [242, 140, 40],   // orange
        [121, 85, 72],    // brown
        [0, 150, 136],    // teal
        [156, 39, 176],   // purple
        [96, 125, 139],   // blue-grey
        [233, 30, 99],    // pink
        [63, 81, 181],    // indigo
        [255, 87, 34],    // deep orange
        [139, 195, 74],   // light green
        [0, 188, 212],    // cyan
        [158, 158, 158],  // grey
        [255, 152, 0],    // amber
        [103, 58, 183],   // deep purple
        [205, 220, 57],   // lime
        [33, 150, 243],   // light blue
        [244, 67, 54],    // red 2
        [0, 121, 107],    // dark teal
        [183, 28, 28],    // dark red
    ];

    private string $fontRegular;

    private string $fontBold;

    public function __construct()
    {
        $this->fontRegular = $this->resolveFont(false);
        $this->fontBold = $this->resolveFont(true);
    }

    /**
     * 3D-style vertical bar chart for contractor compliance (Page 8).
     *
     * @param  list<array{name: string, percentage: float}>  $items
     */
    public function renderComplianceBarChart(array $items, int $width = 1600, int $height = 780): string
    {
        $img = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 30, 30, 30);
        $grid = imagecolorallocate($img, 200, 200, 200);
        $axis = imagecolorallocate($img, 160, 40, 40);
        $bar = imagecolorallocate($img, 242, 140, 40);
        $barDark = imagecolorallocate($img, 200, 100, 20);
        $barTop = imagecolorallocate($img, 255, 180, 90);
        imagefilledrectangle($img, 0, 0, $width, $height, $white);

        $left = 70;
        $right = $width - 30;
        $top = 40;
        $bottom = $height - 160;
        $plotW = $right - $left;
        $plotH = $bottom - $top;

        imagerectangle($img, $left, $top, $right, $bottom, $black);

        foreach ([0, 25, 50, 75, 100] as $tick) {
            $y = (int) round($bottom - ($tick / 100) * $plotH);
            imageline($img, $left, $y, $right, $y, $grid);
            $label = number_format($tick, 2);
            $this->drawText($img, 11, 0, 8, $y - 7, $axis, $label, true);
        }

        $count = max(count($items), 1);
        $slot = $plotW / $count;
        $barW = max(10, min(42, (int) ($slot * 0.55)));
        $depth = 10;

        foreach ($items as $i => $item) {
            $pct = max(0.0, min(100.0, (float) $item['percentage']));
            $barH = (int) round(($pct / 100) * $plotH);
            $x = (int) round($left + ($i + 0.5) * $slot - $barW / 2);
            $y = $bottom - $barH;

            // 3D top
            $topPoints = [
                $x, $y,
                $x + $depth, $y - $depth,
                $x + $barW + $depth, $y - $depth,
                $x + $barW, $y,
            ];
            imagefilledpolygon($img, $topPoints, $barTop);

            // 3D side
            $sidePoints = [
                $x + $barW, $y,
                $x + $barW + $depth, $y - $depth,
                $x + $barW + $depth, $bottom - $depth,
                $x + $barW, $bottom,
            ];
            imagefilledpolygon($img, $sidePoints, $barDark);

            imagefilledrectangle($img, $x, $y, $x + $barW, $bottom, $bar);

            $pctLabel = number_format($pct, 2);
            $this->drawCenteredText($img, 12, $x + (int) ($barW / 2), max($top + 4, $y - 18), $axis, $pctLabel, true);

            $name = $this->truncate((string) ($item['name'] ?? ''), 28);
            $this->drawVerticalText($img, 11, $x + (int) ($barW / 2), $bottom + 12, $axis, $name);
        }

        return $this->toPng($img);
    }

    /**
     * Flat / soft-3D pie chart used for pages 9, 10 and contractor quadrants.
     *
     * @param  list<array{label: string, percentage: float}>  $items
     * @return array{image: string, legend: list<array{label: string, percentage: float, color: string}>}
     */
    public function renderPieChart(array $items, int $size = 700, bool $withLabels = true): array
    {
        $padding = $withLabels ? 220 : 40;
        $width = $size + ($withLabels ? $padding * 2 : $padding);
        $height = $size + ($withLabels ? 120 : $padding);
        $img = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefilledrectangle($img, 0, 0, $width, $height, $white);

        $cx = (int) ($width / 2);
        $cy = (int) ($height / 2) - ($withLabels ? 10 : 0);
        $rx = (int) ($size * 0.38);
        $ry = (int) ($size * 0.30);
        $depth = 18;

        $total = array_sum(array_map(fn ($i) => max(0.0, (float) $i['percentage']), $items));
        if ($total <= 0) {
            $grey = imagecolorallocate($img, 220, 220, 220);
            imagefilledellipse($img, $cx, $cy, $rx * 2, $ry * 2, $grey);

            return ['image' => $this->toPng($img), 'legend' => []];
        }

        // Shadow / 3D depth rings
        for ($d = $depth; $d >= 1; $d--) {
            $angle = -90.0;
            foreach ($items as $idx => $item) {
                $pct = max(0.0, (float) $item['percentage']);
                if ($pct <= 0) {
                    continue;
                }
                $sweep = ($pct / $total) * 360.0;
                $color = $this->allocateDarker($img, self::PIE_COLORS[$idx % count(self::PIE_COLORS)], 0.75);
                $this->drawPieSlice($img, $cx, $cy + $d, $rx, $ry, $angle, $angle + $sweep, $color);
                $angle += $sweep;
            }
        }

        $legend = [];
        $angle = -90.0;
        foreach ($items as $idx => $item) {
            $pct = max(0.0, (float) $item['percentage']);
            if ($pct <= 0) {
                continue;
            }
            $sweep = ($pct / $total) * 360.0;
            $rgb = self::PIE_COLORS[$idx % count(self::PIE_COLORS)];
            $color = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
            $this->drawPieSlice($img, $cx, $cy, $rx, $ry, $angle, $angle + $sweep, $color);

            $mid = deg2rad($angle + $sweep / 2);
            $label = $this->formatViolationLabel((string) ($item['label'] ?? ''), (string) ($item['code'] ?? ''));
            $pctText = number_format($pct, 1).'%';

            if ($withLabels) {
                $lx = (int) round($cx + cos($mid) * ($rx + 70));
                $ly = (int) round($cy + sin($mid) * ($ry + 45));
                $ex = (int) round($cx + cos($mid) * ($rx * 0.85));
                $ey = (int) round($cy + sin($mid) * ($ry * 0.85));
                $line = imagecolorallocate($img, 160, 160, 160);
                imageline($img, $ex, $ey, $lx, $ly, $line);

                $textColor = imagecolorallocate($img, 120, 30, 30);
                $pctColor = imagecolorallocate($img, 130, 130, 130);
                $alignRight = $lx < $cx;
                $this->drawText($img, 11, 0, $alignRight ? $lx - $this->textWidth(11, $label) : $lx, $ly - 12, $textColor, $label, false);
                $this->drawText($img, 10, 0, $alignRight ? $lx - $this->textWidth(10, $pctText) : $lx, $ly + 4, $pctColor, $pctText, false);
            }

            $legend[] = [
                'label' => $label,
                'percentage' => $pct,
                'color' => sprintf('#%02X%02X%02X', $rgb[0], $rgb[1], $rgb[2]),
            ];

            $angle += $sweep;
        }

        return ['image' => $this->toPng($img), 'legend' => $legend];
    }

    /**
     * Compact pie for a contractor quadrant (top 5).
     *
     * @param  list<array{label: string, code?: string, percentage: float}>  $items
     */
    public function renderContractorPie(array $items, int $size = 520): string
    {
        return $this->renderPieChart($items, $size, true)['image'];
    }

    private function drawPieSlice($img, int $cx, int $cy, int $rx, int $ry, float $start, float $end, int $color): void
    {
        $points = [$cx, $cy];
        for ($a = $start; $a <= $end; $a += 1.5) {
            $rad = deg2rad($a);
            $points[] = (int) round($cx + cos($rad) * $rx);
            $points[] = (int) round($cy + sin($rad) * $ry);
        }
        $rad = deg2rad($end);
        $points[] = (int) round($cx + cos($rad) * $rx);
        $points[] = (int) round($cy + sin($rad) * $ry);

        if (count($points) >= 6) {
            imagefilledpolygon($img, $points, $color);
        }
    }

    /**
     * @param  resource|\GdImage  $img
     * @param  array{0:int,1:int,2:int}  $rgb
     */
    private function allocateDarker($img, array $rgb, float $factor): int
    {
        return imagecolorallocate(
            $img,
            (int) max(0, min(255, $rgb[0] * $factor)),
            (int) max(0, min(255, $rgb[1] * $factor)),
            (int) max(0, min(255, $rgb[2] * $factor))
        );
    }

    /**
     * @param  resource|\GdImage  $img
     */
    private function drawText($img, float $size, float $angle, int $x, int $y, int $color, string $text, bool $bold): void
    {
        $font = $bold ? $this->fontBold : $this->fontRegular;
        $text = ArabicText::forGd($text);
        if ($font && function_exists('imagettftext')) {
            imagettftext($img, $size, $angle, $x, $y + (int) $size, $color, $font, $text);

            return;
        }
        imagestring($img, 3, $x, $y, $text, $color);
    }

    /**
     * @param  resource|\GdImage  $img
     */
    private function drawCenteredText($img, float $size, int $cx, int $y, int $color, string $text, bool $bold): void
    {
        $w = $this->textWidth($size, $text, $bold);
        $this->drawText($img, $size, 0, $cx - (int) ($w / 2), $y, $color, $text, $bold);
    }

    /**
     * @param  resource|\GdImage  $img
     */
    private function drawVerticalText($img, float $size, int $x, int $y, int $color, string $text): void
    {
        $font = $this->fontRegular;
        $shaped = ArabicText::forGd($text);
        if ($font && function_exists('imagettftext')) {
            // Rotate 90 so text reads upward along the axis labels like the template.
            imagettftext($img, $size, 90, $x + 4, $y + $this->measureTextWidth($size, $shaped), $color, $font, $shaped);

            return;
        }
        imagestringup($img, 2, $x, $y + 80, $text, $color);
    }

    private function textWidth(float $size, string $text, bool $bold = false): int
    {
        return $this->measureTextWidth($size, ArabicText::forGd($text), $bold);
    }

    private function measureTextWidth(float $size, string $shapedText, bool $bold = false): int
    {
        $font = $bold ? $this->fontBold : $this->fontRegular;
        if ($font && function_exists('imagettfbbox')) {
            $box = imagettfbbox($size, 0, $font, $shapedText);
            if (is_array($box)) {
                return (int) abs($box[2] - $box[0]);
            }
        }

        return strlen($shapedText) * 7;
    }

    private function formatViolationLabel(string $description, string $code): string
    {
        $desc = $this->truncate(trim($description), 36);
        $code = trim($code);
        if ($code === '') {
            return $desc;
        }

        return $desc.' - '.$code;
    }

    private function truncate(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max - 1).'…';
    }

    private function resolveFont(bool $bold): string
    {
        $candidates = $bold
            ? ['C:/Windows/Fonts/arialbd.ttf', 'C:/Windows/Fonts/tahoma.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf']
            : ['C:/Windows/Fonts/arial.ttf', 'C:/Windows/Fonts/tahoma.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return '';
    }

    /**
     * @param  resource|\GdImage  $img
     */
    private function toPng($img): string
    {
        ob_start();
        imagepng($img);
        imagedestroy($img);
        $binary = (string) ob_get_clean();

        return 'data:image/png;base64,'.base64_encode($binary);
    }
}

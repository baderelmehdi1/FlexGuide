<?php

namespace App\Support\Pdf;

use Mpdf\Mpdf;
use RuntimeException;

class AnnotationFlattener
{
    /**
     * Draws shapes onto a throwaway raster copy and embeds it in a self-contained
     * SVG. Labels remain Unicode text so mPDF can shape Arabic with its OTL font.
     * The stored original is never modified. No annotations means no temp file.
     */
    public static function flatten(string $sourcePath, string $mimeType, array $shapes, Mpdf $pdf): string
    {
        if (empty($shapes)) {
            return $sourcePath;
        }

        $image = match ($mimeType) {
            'image/png' => imagecreatefrompng($sourcePath),
            'image/webp' => imagecreatefromwebp($sourcePath),
            default => imagecreatefromjpeg($sourcePath),
        };

        if (! $image) {
            return $sourcePath;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $gold = imagecolorallocate($image, 218, 175, 55);
        $navy = imagecolorallocate($image, 10, 26, 58);

        try {
            foreach ($shapes as $shape) {
                if (($shape['type'] ?? null) === 'box') {
                    self::drawBox($image, $shape, $width, $height, $gold);
                } elseif (($shape['type'] ?? null) === 'point') {
                    self::drawPoint($image, $shape, $width, $height, $gold, $navy);
                }
            }

            ob_start();
            try {
                imagepng($image);
                $raster = base64_encode(ob_get_contents());
            } finally {
                ob_end_clean();
            }
        } finally {
            imagedestroy($image);
        }

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="%d" height="%d" viewBox="0 0 %d %d">',
            $width, $height, $width, $height,
        );
        $svg .= sprintf('<image x="0" y="0" width="%d" height="%d" xlink:href="data:image/png;base64,%s"/>', $width, $height, $raster);
        $svg .= self::labels($shapes, $width, $height, $pdf).'</svg>';

        // mPDF detects SVG from its contents; no extension or second temp file
        // is needed. The caller owns and removes this single generated file.
        $tempPath = tempnam(sys_get_temp_dir(), 'flexcube_flat_');
        if ($tempPath === false) {
            throw new RuntimeException('Unable to create the annotated PDF image.');
        }
        try {
            if (file_put_contents($tempPath, $svg) === false) {
                throw new RuntimeException('Unable to write the annotated PDF image.');
            }
        } catch (\Throwable $error) {
            @unlink($tempPath);
            throw $error;
        }

        return $tempPath;
    }

    private static function drawBox($image, array $shape, int $width, int $height, int $color): void
    {
        $x1 = (int) round($shape['x'] / 100 * $width);
        $y1 = (int) round($shape['y'] / 100 * $height);
        $x2 = (int) round(($shape['x'] + $shape['w']) / 100 * $width);
        $y2 = (int) round(($shape['y'] + $shape['h']) / 100 * $height);
        $thickness = max(2, (int) round($width * 0.004));

        for ($i = 0; $i < $thickness; $i++) {
            imagerectangle($image, $x1 - $i, $y1 - $i, $x2 + $i, $y2 + $i, $color);
        }
    }

    private static function drawPoint($image, array $shape, int $width, int $height, int $fill, int $outline): void
    {
        $cx = (int) round($shape['x'] / 100 * $width);
        $cy = (int) round($shape['y'] / 100 * $height);
        $radius = max(8, (int) round($width * 0.012));

        imagefilledellipse($image, $cx, $cy, $radius * 2, $radius * 2, $fill);
        imageellipse($image, $cx, $cy, $radius * 2, $radius * 2, $outline);
    }

    private static function labels(array $shapes, int $width, int $height, Mpdf $pdf): string
    {
        $font = [$pdf->FontFamily, $pdf->FontStyle, $pdf->FontSizePt];
        $svg = '';

        try {
            foreach ($shapes as $shape) {
                $label = trim(preg_replace('/\s+/u', ' ', (string) ($shape['text'] ?? '')));
                if (($shape['type'] ?? null) !== 'point' || $label === '') {
                    continue;
                }

                $padding = min(max(4, $width * 0.004), $width / 8, $height / 8);
                $fontSize = min(max(16, $width * 0.018), $height / 2);
                $pdf->SetFont('plexarabic', '', $fontSize * 72 / $pdf->dpi, false);
                // Unicode font metrics, rather than strlen() on UTF-8 bytes.
                $textWidth = $pdf->GetStringWidth($label) * $pdf->dpi / 25.4;
                $scale = min(1, ($width - 2 * $padding) / max(1, $textWidth));
                $fontSize *= $scale;
                $textWidth *= $scale;
                $boxWidth = $textWidth + 2 * $padding;
                $boxHeight = min($height, $fontSize * 1.5 + 2 * $padding);
                $cx = $shape['x'] / 100 * $width;
                $cy = $shape['y'] / 100 * $height;
                $radius = max(8, (int) round($width * 0.012));
                $x = max(0, min($width - $boxWidth, $cx - $boxWidth / 2));
                $y = $cy - $radius - $boxHeight - $padding;
                if ($y < 0) {
                    $y = $cy + $radius + $padding;
                }
                $y = max(0, min($height - $boxHeight, $y));

                $rtl = (bool) preg_match('/^[^\p{L}]*(?=\p{L})\p{Arabic}/u', $label);
                $text = $label;
                if (preg_match('/\p{Arabic}/u', $label)) {
                    // mPDF's SVG reader inherits the document direction. Embed
                    // each mixed/Arabic label with its own first-strong direction.
                    $text = ($rtl ? "\u{202B}" : "\u{202A}").$text."\u{202C}";
                }
                $svg .= sprintf('<rect x="%.3F" y="%.3F" width="%.3F" height="%.3F" fill="#0a1a3a"/>', $x, $y, $boxWidth, $boxHeight);
                $svg .= sprintf(
                    '<text x="%.3F" y="%.3F" font-family="plexarabic" font-size="%.3F" direction="%s" text-anchor="middle" fill="#ffffff">%s</text>',
                    $x + $boxWidth / 2, $y + $padding + $fontSize, $fontSize, $rtl ? 'rtl' : 'ltr',
                    htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                );
            }
        } finally {
            $pdf->SetFont($font[0], $font[1], $font[2], false);
        }

        return $svg;
    }
}

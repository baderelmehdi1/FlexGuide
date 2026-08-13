<?php

namespace App\Support\Pdf;

class AnnotationFlattener
{
    /**
     * Burns annotation shapes onto a throwaway COPY of the image for PDF
     * embedding -- unlike redaction, this never touches the stored original.
     * Returns the source path unchanged when there's nothing to draw, so
     * callers only need to clean up temp files they actually got back.
     */
    public static function flatten(string $sourcePath, string $mimeType, array $shapes): string
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
        $white = imagecolorallocate($image, 255, 255, 255);

        foreach ($shapes as $shape) {
            if (($shape['type'] ?? null) === 'box') {
                self::drawBox($image, $shape, $width, $height, $gold);
            } elseif (($shape['type'] ?? null) === 'point') {
                self::drawPoint($image, $shape, $width, $height, $gold, $navy, $white);
            }
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'flexcube_flat_').'.png';
        imagepng($image, $tempPath);
        imagedestroy($image);

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

    private static function drawPoint($image, array $shape, int $width, int $height, int $fill, int $outline, int $textColor): void
    {
        $cx = (int) round($shape['x'] / 100 * $width);
        $cy = (int) round($shape['y'] / 100 * $height);
        $radius = max(8, (int) round($width * 0.012));

        imagefilledellipse($image, $cx, $cy, $radius * 2, $radius * 2, $fill);
        imageellipse($image, $cx, $cy, $radius * 2, $radius * 2, $outline);

        $label = trim((string) ($shape['text'] ?? ''));

        if ($label === '') {
            return;
        }

        $font = 5;
        $textWidth = imagefontwidth($font) * strlen($label);
        $boxX = $cx - (int) ($textWidth / 2) - 4;
        $boxY = $cy - $radius - 20;

        imagefilledrectangle($image, $boxX, $boxY, $boxX + $textWidth + 8, $boxY + 16, $outline);
        imagestring($image, $font, $boxX + 4, $boxY + 2, $label, $textColor);
    }
}

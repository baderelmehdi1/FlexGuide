<?php

namespace Tests\Unit;

use App\Support\Pdf\AnnotationFlattener;
use DOMDocument;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Tests\TestCase;

class PdfAnnotationFlattenerTest extends TestCase
{
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    private function source(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'annotation_test_');
        $this->temporaryFiles[] = $path;
        $image = imagecreatetruecolor(1000, 400);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imagepng($image, $path);
        imagedestroy($image);

        return $path;
    }

    private function renderer(): Mpdf
    {
        return new Mpdf([
            'fontDir' => [...(new ConfigVariables)->getDefaults()['fontDir'], resource_path('fonts')],
            'fontdata' => (new FontVariables)->getDefaults()['fontdata'] + [
                'plexarabic' => ['R' => 'IBMPlexSansArabic-Regular.ttf', 'useOTL' => 0xFF],
            ],
        ]);
    }

    private function flatten(string $source, array $shapes): DOMDocument
    {
        $path = AnnotationFlattener::flatten($source, 'image/png', $shapes, $this->renderer());
        $this->temporaryFiles[] = $path;
        $svg = file_get_contents($path);
        $this->assertSame('<svg', substr($svg, 0, 4));
        $document = new DOMDocument;
        $this->assertTrue($document->loadXML($svg));

        return $document;
    }

    public function test_arabic_english_and_mixed_labels_remain_unicode_text_in_the_export(): void
    {
        $source = $this->source();
        $labels = ['اضغط هنا', 'Click here', 'اضغط Save ثم تابع 123'];
        $document = $this->flatten($source, array_map(fn ($label) => [
            'type' => 'point', 'x' => 50, 'y' => 50, 'text' => $label,
        ], $labels));

        $texts = $document->getElementsByTagName('text');
        $this->assertCount(3, $texts);
        foreach ($labels as $index => $label) {
            $this->assertStringContainsString($label, $texts->item($index)->textContent);
            $this->assertSame('plexarabic', $texts->item($index)->getAttribute('font-family'));
        }
        // Pure Latin labels must not display unsupported bidi-control glyphs.
        $this->assertSame('Click here', $texts->item(1)->textContent);
    }

    public function test_labels_are_escaped_and_cannot_inject_svg_markup(): void
    {
        $label = 'حساب <image href="https://example.test/private"/> & "123"';
        $document = $this->flatten($this->source(), [
            ['type' => 'point', 'x' => 50, 'y' => 50, 'text' => $label],
        ]);

        $this->assertCount(1, $document->getElementsByTagName('image'));
        $this->assertCount(0, $document->getElementsByTagName('script'));
        $this->assertStringContainsString($label, $document->getElementsByTagName('text')->item(0)->textContent);
        $this->assertStringStartsWith('data:image/png;base64,', $document->getElementsByTagName('image')->item(0)
            ->getAttributeNS('http://www.w3.org/1999/xlink', 'href'));
    }

    public function test_shapes_are_drawn_into_a_copy_and_original_pixels_are_unchanged(): void
    {
        $source = $this->source();
        $before = hash_file('sha256', $source);
        $document = $this->flatten($source, [
            ['type' => 'box', 'x' => 10, 'y' => 10, 'w' => 20, 'h' => 20],
            ['type' => 'point', 'x' => 50, 'y' => 50, 'text' => 'اضغط هنا'],
        ]);
        $data = $document->getElementsByTagName('image')->item(0)
            ->getAttributeNS('http://www.w3.org/1999/xlink', 'href');
        $image = imagecreatefromstring(base64_decode(substr($data, strlen('data:image/png;base64,'))));

        $this->assertSame(0xDAAF37, imagecolorat($image, 500, 200) & 0xFFFFFF);
        $this->assertSame(0xDAAF37, imagecolorat($image, 100, 40) & 0xFFFFFF);
        $this->assertSame(0xFFFFFF, imagecolorat($image, 900, 350) & 0xFFFFFF);
        $this->assertSame($before, hash_file('sha256', $source));
        imagedestroy($image);
    }

    public function test_long_labels_at_image_edges_fit_inside_the_image(): void
    {
        $document = $this->flatten($this->source(), [
            ['type' => 'point', 'x' => 0, 'y' => 0, 'text' => str_repeat('اختبار ', 30)],
            ['type' => 'point', 'x' => 100, 'y' => 100, 'text' => 'اضغط Save ثم تابع 123'],
        ]);

        foreach ($document->getElementsByTagName('rect') as $rectangle) {
            $x = (float) $rectangle->getAttribute('x');
            $y = (float) $rectangle->getAttribute('y');
            $this->assertGreaterThanOrEqual(0, $x);
            $this->assertGreaterThanOrEqual(0, $y);
            $this->assertLessThanOrEqual(1000.001, $x + (float) $rectangle->getAttribute('width'));
            $this->assertLessThanOrEqual(400.001, $y + (float) $rectangle->getAttribute('height'));
        }
    }

    public function test_an_image_without_annotations_is_returned_unchanged(): void
    {
        $source = $this->source();
        $this->assertSame($source, AnnotationFlattener::flatten($source, 'image/png', [], $this->renderer()));
    }

    public function test_label_direction_uses_the_first_letter_instead_of_the_guide_or_leading_digits(): void
    {
        $document = $this->flatten($this->source(), [
            ['type' => 'point', 'x' => 30, 'y' => 40, 'text' => '١٢٣ Save ثم اضغط'],
            ['type' => 'point', 'x' => 70, 'y' => 70, 'text' => '123 اضغط Save'],
        ]);

        $texts = $document->getElementsByTagName('text');
        $this->assertSame('ltr', $texts->item(0)->getAttribute('direction'));
        $this->assertSame('rtl', $texts->item(1)->getAttribute('direction'));
    }
}

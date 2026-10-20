<?php

namespace Tests\Feature;

use App\Enums\GuideStatus;
use App\Models\Category;
use App\Models\Guide;
use App\Models\Step;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GuidePdfTest extends TestCase
{
    use RefreshDatabase;

    public static function guideLanguages(): array
    {
        return [['ar'], ['en']];
    }

    #[DataProvider('guideLanguages')]
    public function test_unicode_annotation_labels_export_in_both_guide_directions_without_changing_media(string $language): void
    {
        Storage::fake('public');
        $category = Category::create(['name' => 'اختبار التوضيحات', 'slug' => 'annotation-test']);
        $guide = Guide::create([
            'category_id' => $category->id,
            'title' => $language === 'ar' ? 'اختبار توضيحات الصور' : 'Screenshot annotation test',
            'slug' => 'annotation-test-'.$language,
            'language' => $language,
            'status' => GuideStatus::Published,
        ]);
        $step = $guide->steps()->create([
            'order' => 1,
            'title' => $language === 'ar' ? 'الخطوة الأولى' : 'Step one',
            'body' => '<p>افتح الشاشة ثم اضغط على زر الحفظ.</p>',
            'warning' => 'تأكد من صحة البيانات قبل المتابعة.',
        ]);
        $file = UploadedFile::fake()->image('synthetic.png', 1200, 800);
        $image = imagecreatefrompng($file->getPathname());
        imagefill($image, 0, 0, imagecolorallocate($image, 245, 247, 250));
        imagepng($image, $file->getPathname());
        imagedestroy($image);
        $media = $step->addMedia($file)->toMediaCollection('screenshots');
        $annotations = [
            ['type' => 'box', 'x' => 10, 'y' => 10, 'w' => 80, 'h' => 80],
            ['type' => 'point', 'x' => 25, 'y' => 30, 'text' => 'Click here'],
            ['type' => 'point', 'x' => 75, 'y' => 30, 'text' => 'اضغط هنا'],
            ['type' => 'point', 'x' => 50, 'y' => 60, 'text' => 'اضغط Save ثم تابع 123'],
            ['type' => 'point', 'x' => 50, 'y' => 85, 'text' => 'Save ثم اضغط 123'],
            ['type' => 'point', 'x' => 0, 'y' => 0, 'text' => 'اسم <حساب> & 123'],
        ];
        $media->setCustomProperty('annotations', $annotations)->save();
        $originalHash = hash_file('sha256', $media->getPath());
        $thumbnailHash = hash_file('sha256', $media->getPath('thumb'));

        $response = $this->actingAs(User::factory()->create())->get("/guides/{$guide->slug}/pdf");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertSame($originalHash, hash_file('sha256', $media->getPath()));
        $this->assertSame($thumbnailHash, hash_file('sha256', $media->getPath('thumb')));
        $this->assertSame($annotations, $media->fresh()->getCustomProperty('annotations'));

        // Optional synthetic PDFs for manual visual inspection. No real guide
        // data or server-side rasterizer is needed to run the regression suite.
        if (getenv('FLEXCUBE_WRITE_PDF_SAMPLES') === '1') {
            $directory = storage_path('framework/testing/pdf-annotation-samples');
            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
            file_put_contents($directory.'/annotations-'.$language.'.pdf', $response->getContent());
        }
    }

    public function test_published_guide_pdf_downloads_as_a_valid_pdf(): void
    {
        Storage::fake('public');

        $category = Category::create(['name' => 'Deposits', 'slug' => 'deposits', 'order' => 1]);
        $guide = Guide::create([
            'category_id' => $category->id,
            'title' => 'Opening an account',
            'slug' => 'opening-an-account',
            'language' => 'en',
            'status' => GuideStatus::Published,
        ]);
        $step = Step::create([
            'guide_id' => $guide->id,
            'order' => 1,
            'title' => 'Open STDCUSAC',
            'body' => '<p>Do the thing.</p>',
            'warning' => 'Careful here.',
        ]);
        $media = $step->addMedia(UploadedFile::fake()->image('screen.png', 200, 100))->toMediaCollection('screenshots');
        $media->setCustomProperty('annotations', [
            ['type' => 'box', 'x' => 10, 'y' => 10, 'w' => 20, 'h' => 20],
            ['type' => 'point', 'x' => 50, 'y' => 50, 'text' => 'Click'],
        ]);
        $media->save();

        $response = $this->actingAs(User::factory()->create())->get("/guides/{$guide->slug}/pdf");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_arabic_guide_pdf_renders_with_rtl_directionality(): void
    {
        Storage::fake('public');

        $category = Category::create(['name' => 'الودائع', 'slug' => 'deposits-ar', 'order' => 1]);
        $guide = Guide::create([
            'category_id' => $category->id,
            'title' => 'فتح حساب',
            'slug' => 'fath-hisab',
            'language' => 'ar',
            'status' => GuideStatus::Published,
        ]);
        Step::create([
            'guide_id' => $guide->id,
            'order' => 1,
            'title' => 'الخطوة الأولى',
            'body' => '<p>افتح الشاشة.</p>',
        ]);

        $response = $this->actingAs(User::factory()->create())->get("/guides/{$guide->slug}/pdf");

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_draft_guide_pdf_is_forbidden_to_unrelated_users(): void
    {
        $category = Category::create(['name' => 'Deposits', 'slug' => 'deposits', 'order' => 1]);
        $owner = User::factory()->create();
        $guide = Guide::create([
            'category_id' => $category->id,
            'title' => 'Draft guide',
            'slug' => 'draft-guide',
            'language' => 'en',
            'status' => GuideStatus::Draft,
            'created_by' => $owner->id,
        ]);

        $this->actingAs(User::factory()->create())
            ->get("/guides/{$guide->slug}/pdf")
            ->assertForbidden();
    }
}

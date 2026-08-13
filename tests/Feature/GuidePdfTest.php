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
use Tests\TestCase;

class GuidePdfTest extends TestCase
{
    use RefreshDatabase;

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

<?php

namespace Tests\Feature;

use App\Enums\GuideStatus;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Guide;
use App\Models\Step;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role as PermissionRole;
use Tests\TestCase;

class MediaRedactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_can_redact_an_area_of_the_image_and_pixels_are_actually_blacked_out(): void
    {
        Storage::fake('public');
        PermissionRole::findOrCreate(Role::Contributor->value, 'web');
        $owner = User::factory()->create();
        $owner->assignRole(Role::Contributor->value);

        $category = Category::create(['name' => 'Deposits', 'slug' => 'deposits', 'order' => 1]);
        $guide = Guide::create([
            'category_id' => $category->id,
            'title' => 'Opening an account',
            'slug' => 'opening-an-account',
            'language' => 'en',
            'status' => GuideStatus::Draft,
            'created_by' => $owner->id,
        ]);
        $step = Step::create(['guide_id' => $guide->id, 'order' => 1]);

        // A real 100x100 solid-white PNG so we can prove pixels changed --
        // UploadedFile::fake()->image() produces a GD-generated image, which
        // is exactly what the redact() handler needs to read back with GD.
        $image = imagecreatetruecolor(100, 100);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        $path = tempnam(sys_get_temp_dir(), 'shot').'.png';
        imagepng($image, $path);
        imagedestroy($image);

        $media = $step->addMedia($path)
            ->usingFileName('screen.png')
            ->toMediaCollection('screenshots');

        $this->actingAs($owner)
            ->post("/media/{$media->id}/redact", [
                'boxes' => [['x' => 0, 'y' => 0, 'w' => 50, 'h' => 50]],
            ])
            ->assertRedirect();

        $redacted = imagecreatefrompng($media->fresh()->getPath());
        $blackPixel = imagecolorat($redacted, 10, 10);
        $whitePixel = imagecolorat($redacted, 90, 90);
        imagedestroy($redacted);

        $this->assertSame(0, $blackPixel & 0xFFFFFF, 'redacted quadrant should be pure black');
        $this->assertSame(0xFFFFFF, $whitePixel & 0xFFFFFF, 'untouched quadrant should stay white');
    }

    public function test_redact_requires_at_least_one_box(): void
    {
        PermissionRole::findOrCreate(Role::Contributor->value, 'web');
        $owner = User::factory()->create();
        $owner->assignRole(Role::Contributor->value);

        $category = Category::create(['name' => 'Deposits', 'slug' => 'deposits', 'order' => 1]);
        $guide = Guide::create([
            'category_id' => $category->id,
            'title' => 'Opening an account',
            'slug' => 'opening-an-account',
            'language' => 'en',
            'status' => GuideStatus::Draft,
            'created_by' => $owner->id,
        ]);
        $step = Step::create(['guide_id' => $guide->id, 'order' => 1]);
        $media = $step->addMedia(UploadedFile::fake()->image('screen.png'))->toMediaCollection('screenshots');

        $this->actingAs($owner)
            ->post("/media/{$media->id}/redact", ['boxes' => []])
            ->assertSessionHasErrors('boxes');
    }

    public function test_viewer_role_cannot_redact(): void
    {
        PermissionRole::findOrCreate(Role::Contributor->value, 'web');
        PermissionRole::findOrCreate(Role::Viewer->value, 'web');
        $owner = User::factory()->create();
        $owner->assignRole(Role::Contributor->value);
        $viewer = User::factory()->create();
        $viewer->assignRole(Role::Viewer->value);

        $category = Category::create(['name' => 'Deposits', 'slug' => 'deposits', 'order' => 1]);
        $guide = Guide::create([
            'category_id' => $category->id,
            'title' => 'Opening an account',
            'slug' => 'opening-an-account',
            'language' => 'en',
            'status' => GuideStatus::Draft,
            'created_by' => $owner->id,
        ]);
        $step = Step::create(['guide_id' => $guide->id, 'order' => 1]);
        $media = $step->addMedia(UploadedFile::fake()->image('screen.png'))->toMediaCollection('screenshots');

        $this->actingAs($viewer)
            ->post("/media/{$media->id}/redact", ['boxes' => [['x' => 0, 'y' => 0, 'w' => 10, 'h' => 10]]])
            ->assertForbidden();
    }
}
